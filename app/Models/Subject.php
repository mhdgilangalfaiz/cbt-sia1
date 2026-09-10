<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    // relasi 1 to many dengan tabel questions
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    // Relasi Many - to - Many
    public function exam(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class);
    }
}
