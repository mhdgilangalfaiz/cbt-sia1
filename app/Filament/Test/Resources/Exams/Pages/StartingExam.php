<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\AttemptGrader;
use Filament\Resources\Pages\Page;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class StartingExam extends Page
{
    // toleransi (detik) untuk jeda jaringan saat jawaban dikirim di akhir waktu
    private const GRACE_SECONDS = 30;

    protected static string $resource = ExamResource::class;

    protected static ?string $title = 'Ujian';

    protected string $view = 'filament.test.resources.exams.pages.starting-exam';

    #[Locked]
    public int $examId;

    #[Locked]
    public array $collections = [];

    // pilihan siswa: [question_id => answer_id]
    public array $answers = [];

    #[Locked]
    public ?array $result = null;

    // sisa waktu (detik) saat halaman dibuka, dihitung di server
    #[Locked]
    public ?int $secondsLeft = null;

    public function mount(Exam $exam): void
    {
        $this->examId = $exam->id;

        $attempt = $this->currentAttempt();

        if ($attempt) {
            // sudah selesai: tampilkan hasil
            if ($attempt->submitted_at !== null) {
                $this->result = $this->formatResult($attempt, $exam);

                return;
            }

            // masih mengerjakan, tapi waktunya habis saat siswa tidak membuka halaman
            if ($attempt->deadline_at && now()->greaterThan($attempt->deadline_at)) {
                $attempt = app(AttemptGrader::class)->finalize($attempt, $attempt->deadline_at);
                $this->result = $this->formatResult($attempt, $exam);

                return;
            }

            // masih dalam waktu: lanjutkan soal yang sama
            $this->resume($attempt);

            return;
        }

        // ujian baru: harus tersedia dan sedang berlangsung
        $end = $exam->endsAt();

        abort_unless(
            $exam->is_available
                && $exam->started_at <= now()
                && (! $end || $end >= now()),
            403
        );

        $attempt = $this->begin($exam);

        if ($attempt) {
            $this->resume($attempt);
        }
    }

    // Dipanggil otomatis setiap siswa memilih jawaban (wire:model.live)
    public function updatedAnswers($value, $key): void
    {
        // tidak perlu render ulang halaman, pilihan sudah terlihat di browser
        $this->skipRender();

        $attempt = $this->currentAttempt();

        if (! $attempt || $attempt->submitted_at !== null || now()->greaterThan($attempt->deadline_at)) {
            return;
        }

        $row = $attempt->answers()->where('question_id', (int) $key)->first();

        // jawaban harus salah satu pilihan yang memang ditampilkan untuk soal itu
        if (! $row || ! filled($value) || ! in_array((int) $value, $row->option_order ?? [], true)) {
            return;
        }

        $row->update(['answer_id' => (int) $value]);
    }

    public function submit(): void
    {
        if ($this->result !== null) {
            return;
        }

        $exam = Exam::findOrFail($this->examId);
        $attempt = $this->currentAttempt();

        if (! $attempt) {
            return;
        }

        if ($attempt->submitted_at !== null) {
            $this->showResult($attempt, $exam);

            return;
        }

        $now = now();
        $deadline = $attempt->deadline_at;
        $inTime = $now->lessThanOrEqualTo($deadline->copy()->addSeconds(self::GRACE_SECONDS));

        // Tepat waktu (atau dalam toleransi): simpan pilihan terakhir dari browser.
        // Terlambat: pilihan dari browser DIABAIKAN, hanya jawaban yang sudah tersimpan yang dinilai.
        if ($inTime) {
            $this->persistAnswers($attempt);
        }

        // waktu pengumpulan tidak pernah dicatat melewati batas waktu
        $at = $now->lessThan($deadline) ? $now : $deadline->copy();

        $attempt = app(AttemptGrader::class)->finalize($attempt, $at);

        $this->showResult($attempt, $exam);
    }

    private function currentAttempt(): ?ExamAttempt
    {
        return ExamAttempt::where('exam_id', $this->examId)
            ->where('user_id', Auth::id())
            ->first();
    }

    // Membuat percobaan baru: memilih soal acak, lalu menyimpan susunannya
    private function begin(Exam $exam): ?ExamAttempt
    {
        $rows = [];
        $position = 0;

        foreach ($exam->subjects as $subject) {
            $questions = $subject->questions()
                ->where('is_active', true)
                ->with(['answers' => fn ($q) => $q
                    ->where('is_active', true)
                    ->select('id', 'question_id')])
                ->inRandomOrder()
                ->limit($subject->pivot->qty)
                ->get(['id', 'subject_id']);

            foreach ($questions as $q) {
                $rows[] = [
                    'question_id'  => $q->id,
                    'position'     => $position++,
                    'option_order' => $q->answers->pluck('id')->shuffle()->values()->all(),
                ];
            }
        }

        // ujian tanpa soal: jangan buat percobaan, supaya siswa tidak "terkunci"
        if ($rows === []) {
            return null;
        }

        $now = now();
        $end = $exam->endsAt();
        $byDuration = $now->copy()->addMinutes($exam->duration);

        // batas siswa = yang lebih awal antara (mulai + durasi) dan (ujian berakhir)
        $deadline = ($end && $end->lessThan($byDuration)) ? $end->copy() : $byDuration;

        try {
            return DB::transaction(function () use ($exam, $now, $deadline, $rows) {
                $attempt = ExamAttempt::create([
                    'exam_id'     => $exam->id,
                    'user_id'     => Auth::id(),
                    'started_at'  => $now,
                    'deadline_at' => $deadline,
                ]);

                $attempt->answers()->createMany($rows);

                return $attempt;
            });
        } catch (UniqueConstraintViolationException) {
            // dua tab terbuka bersamaan: pakai percobaan yang sudah terbentuk
            return $this->currentAttempt();
        }
    }

    // Menyusun ulang soal dari data tersimpan (urutan & jawaban tetap sama setelah refresh)
    private function resume(ExamAttempt $attempt): void
    {
        $rows = $attempt->answers()->orderBy('position')->get();

        $questions = Question::withTrashed()
            ->with('subject')
            ->whereIn('id', $rows->pluck('question_id')->filter())
            ->get()
            ->keyBy('id');

        $answerIds = $rows->pluck('option_order')->flatten()->unique();

        $options = Answer::withTrashed()
            ->whereIn('id', $answerIds)
            ->get(['id', 'question_id', 'text'])
            ->keyBy('id');

        $groups = [];
        $selected = [];

        foreach ($rows as $row) {
            $q = $questions[$row->question_id] ?? null;

            if (! $q) {
                continue;
            }

            $groups[$q->subject_id] ??= [
                'id'    => $q->subject_id,
                'name'  => $q->subject?->name ?? 'Soal',
                'soals' => [],
            ];

            // is_correct tidak pernah dikirim ke browser
            $groups[$q->subject_id]['soals'][] = [
                'id'      => $q->id,
                'payload' => $q->payload,
                'answers' => collect($row->option_order ?? [])
                    ->map(fn ($id) => $options[$id] ?? null)
                    ->filter()
                    ->map(fn ($a) => ['id' => $a->id, 'text' => $a->text])
                    ->values()
                    ->all(),
            ];

            $selected[$q->id] = $row->answer_id;
        }

        $this->collections = array_values($groups);
        $this->answers = $selected;
        $this->secondsLeft = max(0, $attempt->deadline_at->getTimestamp() - now()->getTimestamp());
    }

    // Menyimpan pilihan terakhir dari browser. Pilihan kosong tidak menimpa jawaban tersimpan.
    private function persistAnswers(ExamAttempt $attempt): void
    {
        foreach ($attempt->answers()->get() as $row) {
            $chosen = $this->answers[$row->question_id] ?? null;

            if (! filled($chosen) || ! in_array((int) $chosen, $row->option_order ?? [], true)) {
                continue;
            }

            if ($row->answer_id !== (int) $chosen) {
                $row->update(['answer_id' => (int) $chosen]);
            }
        }
    }

    private function showResult(ExamAttempt $attempt, Exam $exam): void
    {
        $this->result = $this->formatResult($attempt, $exam);
        $this->collections = [];
        $this->answers = [];
        $this->secondsLeft = null;
    }

    private function formatResult(ExamAttempt $attempt, Exam $exam): array
    {
        return [
            'attempt_id' => $attempt->id,
            'score'      => (float) $attempt->score,
            'correct'    => $attempt->correct_count,
            'total'      => $attempt->total_questions,
            'passed'     => (bool) $attempt->passed,
            'threshold'  => (float) $exam->threshold,
            // true jika dikumpulkan tepat di batas waktu (waktu habis / terlambat)
            'timed_out'  => $attempt->deadline_at !== null
                && $attempt->submitted_at !== null
                && $attempt->submitted_at->greaterThanOrEqualTo($attempt->deadline_at),
        ];
    }
}