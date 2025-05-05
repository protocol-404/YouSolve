<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'quiz_id',
        'user_id',
        'score',
        'total_points',
        'time_spent',
        'status',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'score' => 'integer',
        'total_points' => 'integer',
        'time_spent' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];


    /**
     * Get the user that owns the result.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user answers for the quiz result.
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }
}
