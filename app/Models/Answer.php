<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    // relasi invers ke model Questions
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
