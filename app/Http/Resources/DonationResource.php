<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    /**
     * Memformat data donasi untuk riwayat donor pengguna.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'ticket_code' => $this->ticket_code,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'blood_request' => $this->whenLoaded('bloodRequest', fn () => [
                'patient_name' => $this->bloodRequest->patient_name,
                'blood_type' => $this->bloodRequest->blood_type,
                'hospital_name' => $this->bloodRequest->hospital_name,
                'urgency_level' => $this->bloodRequest->urgency_level,
            ]),
            'donor_event' => $this->whenLoaded('donorEvent', fn () => [
                'title' => $this->donorEvent->title,
                'organizer' => $this->donorEvent->organizer,
                'event_date' => $this->donorEvent->event_date?->toIso8601String(),
                'location_name' => $this->donorEvent->location_name,
            ]),
        ];
    }
}
