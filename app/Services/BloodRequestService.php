<?php

namespace App\Services;

use App\Models\BloodRequest;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Layanan bisnis modul Minta Donor (geo-matching).
 * Memisahkan logika bisnis dari controller agar tetap thin & testable.
 */
class BloodRequestService
{
    public function __construct(
        private readonly GeoService $geoService,
    ) {}

    /**
     * Membuat permohonan darah baru beserta penyimpanan bukti medis.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, mixed $proofFile = null): BloodRequest
    {
        $data['user_id'] = $user->id;
        $data['status'] = 'open';
        $data['bags_fulfilled'] = 0;

        if (! is_null($proofFile)) {
            $data['medical_reference_proof'] = $proofFile->store('medical_proofs');
        }

        return BloodRequest::create($data);
    }

    /**
     * Mencari permohonan darah terdekat dari lokasi pengguna.
     *
     * Query dioptimalkan dengan bounding box pre-filter di SQL untuk
     * memanfaatkan index, lalu dihitung jarak eksak Haversine di PHP.
     *
     * @return Collection<int, array{blood_request: BloodRequest, distance_km: float}>
     */
    public function nearby(float $latitude, float $longitude, float $radiusKm, ?string $bloodType = null)
    {
        $box = $this->geoService->boundingBox($latitude, $longitude, $radiusKm);

        $query = BloodRequest::query()
            ->where('status', 'open')
            ->where('bags_fulfilled', '<', DB::raw('bags_needed'))
            ->whereBetween('latitude', [$box['min_lat'], $box['max_lat']])
            ->whereBetween('longitude', [$box['min_lng'], $box['max_lng']]);

        if ($bloodType) {
            $query->where('blood_type', $bloodType);
        }

        $candidates = $query->limit(100)->get();

        return $candidates
            ->map(function (BloodRequest $request) use ($latitude, $longitude) {
                return [
                    'blood_request' => $request,
                    'distance_km' => round(
                        $this->geoService->distanceKm($latitude, $longitude, (float) $request->latitude, (float) $request->longitude),
                        2
                    ),
                ];
            })
            ->filter(fn (array $item) => $item['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->values();
    }

    /**
     * Konfirmasi pendonor siap membantu permohonan darah.
     * Menghasilkan record matching & tiket donor.
     *
     * @throws RuntimeException
     */
    public function confirm(User $donor, BloodRequest $bloodRequest): Donation
    {
        if ($bloodRequest->status !== 'open') {
            throw new RuntimeException('Permohonan darah ini sudah tidak dapat dikonfirmasi.');
        }

        if ($bloodRequest->user_id === $donor->id) {
            throw new RuntimeException('Anda tidak dapat mengonfirmasi permohonan darah sendiri.');
        }

        $alreadyConfirmed = Donation::where('donor_id', $donor->id)
            ->where('blood_request_id', $bloodRequest->id)
            ->whereIn('status', ['matched', 'confirmed', 'completed'])
            ->exists();

        if ($alreadyConfirmed) {
            throw new RuntimeException('Anda sudah mengonfirmasi permohonan ini.');
        }

        if (is_null($donor->golongan_darah)) {
            throw new RuntimeException('Lengkapi golongan darah di profil sebelum mengonfirmasi donor.');
        }

        if (! $this->geoService->canDonate($donor->golongan_darah, $bloodRequest->blood_type)) {
            throw new RuntimeException('Golongan darah Anda tidak kompatibel dengan permohonan ini.');
        }

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'blood_request_id' => $bloodRequest->id,
            'type' => 'personal',
            'status' => 'confirmed',
            'ticket_code' => $this->generateTicketCode(),
        ]);

        $bloodRequest->update(['status' => 'matched']);

        return $donation;
    }

    /**
     * Membatalkan permohonan darah oleh pemiliknya (status masih open).
     *
     * @throws RuntimeException
     */
    public function cancel(User $user, BloodRequest $bloodRequest): BloodRequest
    {
        if ($bloodRequest->user_id !== $user->id) {
            throw new RuntimeException('Anda bukan pemilik permohonan ini.');
        }

        if ($bloodRequest->status !== 'open') {
            throw new RuntimeException('Permohonan ini sudah tidak dapat dibatalkan.');
        }

        $bloodRequest->update(['status' => 'cancelled']);

        return $bloodRequest->refresh();
    }

    /**
     * Membuat kode tiket unik berformat DNY-XXXXXXXX.
     */
    private function generateTicketCode(): string
    {
        return 'DNY-'.strtoupper(Str::random(8));
    }
}
