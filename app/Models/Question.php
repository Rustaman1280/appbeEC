<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    //
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'text',
        'options',
        'correct_answer',
        'category',
        'difficulty',
        'xp_reward',
        'explanation',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'integer',
        'xp_reward' => 'integer',
    ];

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_question')->withPivot(['sort_order', 'points'])->withTimestamps();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
