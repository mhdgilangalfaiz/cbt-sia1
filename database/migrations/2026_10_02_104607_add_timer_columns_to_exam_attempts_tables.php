<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            // kapan siswa menekan "Mulai" dan batas akhir pengerjaannya
            $table->dateTime('started_at')->nullable()->after('user_id');
            $table->dateTime('deadline_at')->nullable()->after('started_at');
            // null = masih mengerjakan
            $table->dateTime('submitted_at')->nullable()->change();
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            // urutan soal & urutan pilihan disimpan, supaya refresh tidak mengacak ulang
            $table->unsignedInteger('position')->default(0)->after('exam_attempt_id');
            $table->json('option_order')->nullable()->after('answer_id');
            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropUnique(['exam_attempt_id', 'question_id']);
            $table->dropColumn(['position', 'option_order']);
        });

        // percobaan yang belum selesai dibuang agar kolom bisa dikembalikan NOT NULL
        DB::table('exam_attempts')->whereNull('submitted_at')->delete();

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dateTime('submitted_at')->nullable(false)->change();
            $table->dropColumn(['started_at', 'deadline_at']);
        });
    }
};