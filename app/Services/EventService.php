<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\DonorEvent;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Layanan bisnis modul Event Donor (DonorYuks Event).
 */
class EventService
{
    public function __construct(
        private readonly GeoService $geoService,
    ) {}

    /**
     * Mencari daftar event donor terdekat dengan filter tanggal & lokasi.
     * Hasil di-map dengan jarak lalu di-pagination.
     */
    public function list(?float $latitude, ?float $longitude, ?float $radiusKm, ?string $date, int $perPage = 15): LengthAwarePaginator
    {
        $query = DonorEvent::query()
            ->withCount('donations as booked_count')
            ->orderBy('event_date');

        if ($date) {
            $query->whereDate('event_date', $date);
        }

        $hasLocation = ! is_null($latitude) && ! is_null($longitude);

        $events = $query->get();

        if ($hasLocation) {
            $radius = $radiusKm ?? 25;

            $box = $this->geoService->boundingBox($latitude, $longitude, $radius);

            $events = $events
                ->filter(function (DonorEvent $event) use ($box) {
                    return (float) $event->latitude >= $box['min_lat']
                        && (float) $event->latitude <= $box['max_lat']
                        && (float) $event->longitude >= $box['min_lng']
                        && (float) $event->longitude <= $box['max_lng'];
                });
        }

        $items = $events
            ->map(function (DonorEvent $event) use ($hasLocation, $latitude, $longitude) {
                return [
                    'event' => $event,
                    'distance_km' => $hasLocation
                        ? round($this->geoService->distanceKm($latitude, $longitude, (float) $event->latitude, (float) $event->longitude), 2)
                        : null,
                ];
            })
            ->when($hasLocation, fn (Collection $items) => $items->filter(fn (array $item) => $item['distance_km'] <= ($radiusKm ?? 25)))
            ->sortBy(fn (array $item) => $item['distance_km'] ?? $item['event']->event_date->timestamp)
            ->values();

        $page = max((int) Paginator::resolveCurrentPage(), 1);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * Booking pendaftaran event donor dan menghasilkan tiket.
     *
     * @throws RuntimeException
     */
    public function book(User $user, DonorEvent $donorEvent): Donation
    {
        if ($donorEvent->event_date->isPast()) {
            throw new RuntimeException('Event donor sudah berakhir.');
        }

        $bookedCount = Donation::where('donor_event_id', $donorEvent->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        if ($bookedCount >= $donorEvent->quota) {
            throw new RuntimeException('Kuota event donor sudah penuh.');
        }

        $alreadyBooked = Donation::where('donor_id', $user->id)
            ->where('donor_event_id', $donorEvent->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->exists();

        if ($alreadyBooked) {
            throw new RuntimeException('Anda sudah terdaftar pada event ini.');
        }

        return Donation::create([
            'donor_id' => $user->id,
            'donor_event_id' => $donorEvent->id,
            'type' => 'event',
            'status' => 'confirmed',
            'ticket_code' => 'EVT-'.strtoupper(Str::random(8)),
        ]);
    }
}
