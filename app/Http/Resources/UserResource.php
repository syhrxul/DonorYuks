<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Memformat data pengguna ke struktur JSON yang aman
     * tanpa membocorkan atribut sensitif (password/token).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'no_hp' => $this->no_hp,
            'golongan_darah' => $this->golongan_darah,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'points' => $this->points,
            'last_donated_at' => $this->last_donated_at?->toIso8601String(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
