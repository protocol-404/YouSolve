<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'first_name',
        'last_name',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the classes that the user belongs to.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_user', 'user_id', 'class_id')
                    ->withTimestamps()
                    ->withPivot('joined_at');
    }

    /**
     * Get the classes that the user trains.
     */
    public function trainedClasses(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'trainer_id');
    }

    /**
     * Get the quizzes that the user created.
     */
    public function createdQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'created_by');
    }

    /**
     * Get the quiz results for the user.
     */
    public function quizResults(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role->name === 'administrator';
    }

    /**
     * Check if the user is a trainer.
     */
    public function isTrainer(): bool
    {
        return $this->role->name === 'trainer';
    }

    /**
     * Check if the user is a candidate.
     */
    public function isCandidate(): bool
    {
        return $this->role->name === 'candidate';
    }
}
