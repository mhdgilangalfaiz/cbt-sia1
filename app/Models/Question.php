<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    // relasi invers ke model subject
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // relasi 1 to many dengan model answer
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    // Mengembalikan daftar jawaban dengan label huruf A, B, C, D... berdasarkan urutan tampil. Ini TIDAK menyimpan huruf ke database
    public function getAnswersWithLetterAttribute()
    {
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        return $this->answers->values()->map(fn ($answer, $index) => [
            'letter' => $letters[$index] ?? '-',
            'id' => $answer->id,
            'text' => $answer->text,
            'is_correct' => $answer->is_correct,
        ]);
    }
}