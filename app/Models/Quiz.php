<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Quiz extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'created_by',
        'difficulty',
        'time_limit',
        'pass_score',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'time_limit' => 'integer',
        'pass_score' => 'integer',
    ];

    /**
     * Get the category that owns the quiz.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user that created the quiz.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions for the quiz.
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the results for the quiz.
     */
    public function results()
    {
        return $this->hasMany(QuizResult::class);
    }

}
