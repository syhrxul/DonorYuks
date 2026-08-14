<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorEventResource extends JsonResource
{
    /**
     * Jarak event dari lokasi pengguna (diisi saat filter lokasi).
     */
    private ?float $distanceKm;

    public function __construct($resource, ?float $distanceKm = null)
    {
        parent::__construct($resource);

        $this->distanceKm = $distanceKm;
    }

    /**
     * Memformat data event donor ke struktur JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'organizer' => $this->organizer,
            'description' => $this->description,
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'event_date' => $this->event_date?->toIso8601String(),
            'quota' => $this->quota,
            'booked_count' => $this->booked_count,
            'distance_km' => $this->distanceKm,
        ];
    }
}
