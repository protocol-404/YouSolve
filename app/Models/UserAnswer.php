<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnswer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quiz_result_id',
        'question_id',
        'answer_id',
        'text_answer',
        'is_correct',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * Get the quiz result that owns the user answer.
     */
    public function quizResult(): BelongsTo
    {
        return $this->belongsTo(QuizResult::class);
    }

    /**
     * Get the question that owns the user answer.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the answer that the user selected.
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
