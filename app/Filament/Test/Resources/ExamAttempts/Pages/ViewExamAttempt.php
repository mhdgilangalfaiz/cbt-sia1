<?php

namespace App\Filament\Test\Resources\ExamAttempts\Pages;

use App\Filament\Test\Resources\ExamAttempts\ExamAttemptResource;
use App\Models\ExamAttempt;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

class ViewExamAttempt extends Page
{
    protected static string $resource = ExamAttemptResource::class;

    protected static ?string $title = 'Detail Hasil Ujian';

    protected string $view = 'filament.test.resources.exam-attempts.pages.view-exam-attempt';

    #[Locked]
    public array $summary = [];

    #[Locked]
    public array $review = [];

    public function mount(ExamAttempt $record): void
    {
        $attempt = $record;

        // riwayat milik siswa lain tidak boleh dibuka
        abort_unless($attempt->submitted_at !== null, 404);

        $attempt->load([
            'exam',
            'answers.question',
            'answers.question.answers' => fn ($q) => $q->withTrashed(),
        ]);

        $exam = $attempt->exam;
        $keyVisible = true;

        $this->summary = [
            'title'       => $exam->title,
            'score'       => (float) $attempt->score,
            'correct'     => $attempt->correct_count,
            'wrong'       => $attempt->total_questions - $attempt->correct_count,
            'total'       => $attempt->total_questions,
            'passed'      => $attempt->passed,
            'threshold'   => (float) $exam->threshold,
            'submitted'   => $attempt->submitted_at->translatedFormat('d F Y, H:i'),
            'key_visible' => $keyVisible,
            'key_at'      => $exam->endsAt()?->translatedFormat('d F Y, H:i'),
        ];

        $this->review = $attempt->answers->map(function ($row) use ($keyVisible) {
            $question = $row->question;

            return [
                'payload'    => $question?->payload,
                'is_correct' => $row->is_correct,
                'answered'   => $row->answer_id !== null,
                'options'    => $question
                    ? $question->answers->map(fn ($a) => [
                        'text'   => $a->text,
                        'chosen' => $a->id === $row->answer_id,
                        // kunci jawaban hanya dikirim ke browser jika boleh ditampilkan
                        'is_key' => $keyVisible && $a->is_correct,
                    ])->values()->all()
                    : [],
            ];
        })->all();
    }
}