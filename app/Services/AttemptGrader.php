<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AttemptGrader
{
    /**
     * Menilai percobaan dari jawaban yang SUDAH TERSIMPAN di database.
     * Aman dipanggil berulang: percobaan yang sudah dinilai tidak diubah lagi.
     */
    public function finalize(ExamAttempt $attempt, ?CarbonInterface $at = null): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $at) {
            // kunci baris agar penilaian oleh siswa dan scheduler tidak bertabrakan
            $locked = ExamAttempt::whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->submitted_at !== null) {
                return $locked;
            }

            $exam = Exam::findOrFail($locked->exam_id);
            $rows = $locked->answers()->with(['question', 'answer'])->get();

            $earned = 0;
            $total = 0;
            $correct = 0;

            foreach ($rows as $row) {
                $points = (int) ($row->question?->score ?? 0);

                // jawaban harus milik soal tersebut
                $isCorrect = $row->answer !== null
                    && $row->answer->question_id == $row->question_id
                    && (bool) $row->answer->is_correct;

                $total += $points;

                if ($isCorrect) {
                    $earned += $points;
                    $correct++;
                }

                if ($row->is_correct !== $isCorrect) {
                    $row->update(['is_correct' => $isCorrect]);
                }
            }

            $score = $total > 0 ? round($earned / $total * 100, 2) : 0;

            $locked->update([
                'correct_count'   => $correct,
                'total_questions' => $rows->count(),
                'score'           => $score,
                'passed'          => $score >= (float) $exam->threshold,
                'submitted_at'    => $at ?? now(),
            ]);

            return $locked;
        });
    }
}