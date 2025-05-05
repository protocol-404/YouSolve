<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'question_id',
        'content',
        'is_correct',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * Get the question that owns the answer
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the user answers for this answer
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }
}
