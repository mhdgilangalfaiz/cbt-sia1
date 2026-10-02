<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_correct' => 'boolean',
        'option_order' => 'array',
    ];

    public function question(): BelongsTo
    {
        $relation = $this->belongsTo(Question::class);
        $relation->withTrashed();

        return $relation;
    }

    public function answer(): BelongsTo
    {
        $relation = $this->belongsTo(Answer::class);
        $relation->withTrashed();

        return $relation;
    }
}