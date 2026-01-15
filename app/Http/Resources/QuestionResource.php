<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'options' => $this->options,
            'correctAnswer' => $this->correct_answer,
            'category' => $this->category,
            'difficulty' => $this->difficulty,
            'xpReward' => $this->xp_reward,
            'explanation' => $this->explanation,
        ];
    }
}
