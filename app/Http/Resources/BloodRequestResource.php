<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BloodRequestResource extends JsonResource
{
    /**
     * Jarak permohonan dari lokasi pengguna (diisi saat query nearby).
     */
    private ?float $distanceKm;

    public function __construct($resource, ?float $distanceKm = null)
    {
        parent::__construct($resource);

        $this->distanceKm = $distanceKm;
    }

    /**
     * Memformat data permohonan darah ke struktur JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_name' => $this->patient_name,
            'blood_type' => $this->blood_type,
            'bags_needed' => $this->bags_needed,
            'bags_fulfilled' => $this->bags_fulfilled,
            'hospital_name' => $this->hospital_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'urgency_level' => $this->urgency_level,
            'status' => $this->status,
            'medical_reference_proof' => $this->medical_reference_proof,
            'distance_km' => $this->distanceKm,
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
