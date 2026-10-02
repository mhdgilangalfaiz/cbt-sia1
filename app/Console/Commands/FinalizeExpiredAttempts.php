<?php

namespace App\Console\Commands;

use App\Models\ExamAttempt;
use App\Services\AttemptGrader;
use Illuminate\Console\Command;

class FinalizeExpiredAttempts extends Command
{
    protected $signature = 'exams:finalize-expired';

    protected $description = 'Menilai otomatis percobaan ujian yang waktunya habis tetapi belum dikirim';

    public function handle(AttemptGrader $grader): int
    {
        $count = 0;

        ExamAttempt::query()
            ->whereNull('submitted_at')
            ->whereNotNull('deadline_at')
            // jeda 60 detik agar kiriman siswa sendiri (toleransi 30 detik) diproses lebih dulu
            ->where('deadline_at', '<', now()->subSeconds(60))
            ->lazyById()
            ->each(function (ExamAttempt $attempt) use ($grader, &$count) {
                $grader->finalize($attempt, $attempt->deadline_at);
                $count++;
            });

        $this->info("{$count} percobaan dinilai otomatis.");

        return self::SUCCESS;
    }
}