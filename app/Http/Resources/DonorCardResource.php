<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorCardResource extends JsonResource
{
    /**
     * Memformat data Kartu Donor Digital beserta ringkasan statistik donor.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'card_number' => 'DNY-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'golongan_darah' => $this->golongan_darah,
            'total_donations' => $this->donations->count(),
            'total_completed' => $this->donations->where('status', 'completed')->count(),
            'total_points' => $this->points,
            'last_donated_at' => $this->last_donated_at?->toIso8601String(),
            'member_since' => $this->created_at?->toIso8601String(),
        ];
    }
}
