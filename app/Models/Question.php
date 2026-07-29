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
}
