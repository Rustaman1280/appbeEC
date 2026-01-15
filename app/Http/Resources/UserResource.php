<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'fullName' => $this->name,
            'avatar' => $this->avatar,
            'level' => $this->level,
            'xp' => $this->xp,
            'totalXP' => $this->total_xp,
            'streak' => $this->streak,
            'badges' => [],
            'joinedAt' => $this->created_at,
        ];
    }
}
