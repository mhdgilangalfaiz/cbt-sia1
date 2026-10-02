<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Exam extends Model
{
    protected $guarded = [];

    protected $casts = [
        'threshold' => 'decimal:2',
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
        'exact_time' => 'boolean',
        'is_available' => 'boolean',
    ];

    // Relasi Many - to - many
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)
            ->withPivot('qty');
    }

    // Semua pengerjaan siswa untuk ujian ini
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    // Waktu ujian berakhir
    public function endsAt(): ?Carbon
    {
        return $this->exact_time
            ? $this->started_at?->copy()->addMinutes($this->duration)
            : $this->expired_at;
    }

    // Apakah masa ujian sudah selesai (dipakai untuk membuka kunci jawaban)
    public function hasEnded(): bool
    {
        $end = $this->endsAt();

        return $end !== null && $end->isPast();
    }
}