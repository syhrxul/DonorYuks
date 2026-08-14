<?php

namespace App\Services;

/**
 * Layanan geolokasi untuk pencocokan donor berbasis jarak (Haversine Formula).
 *
 * Strategi optimasi: bounding box (kotak batas) disaring langsung di SQL
 * memanfaatkan index kolom latitude/longitude, kemudian jarak eksak dihitung
 * dengan Haversine di PHP untuk hasil yang akurat. Untuk dataset sangat besar,
 * direkomendasikan pindah ke Spatial Extension MySQL (ST_Distance_Sphere).
 */
class GeoService
{
    /**
     * Jari-jari Bumi dalam kilometer.
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Membuat bounding box koordinat di sekitar titik pusat sesuai radius.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    public function boundingBox(float $latitude, float $longitude, float $radiusKm): array
    {
        $latDelta = $radiusKm / 111.320;
        $lngDelta = $radiusKm / (111.320 * cos(deg2rad($latitude)));

        return [
            'min_lat' => $latitude - $latDelta,
            'max_lat' => $latitude + $latDelta,
            'min_lng' => $longitude - $lngDelta,
            'max_lng' => $longitude + $lngDelta,
        ];
    }

    /**
     * Menghitung jarak antara dua koordinat dalam kilometer (Haversine Formula).
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Mengecek kompatibilitas golongan darah pendonor terhadap penerima.
     *
     * O- adalah donor universal, sedangkan AB+ adalah penerima universal.
     */
    public function canDonate(string $donorBlood, string $recipientBlood): bool
    {
        $compatibility = [
            'O-' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
            'O+' => ['O+', 'A+', 'B+', 'AB+'],
            'A-' => ['A-', 'A+', 'AB-', 'AB+'],
            'A+' => ['A+', 'AB+'],
            'B-' => ['B-', 'B+', 'AB-', 'AB+'],
            'B+' => ['B+', 'AB+'],
            'AB-' => ['AB-', 'AB+'],
            'AB+' => ['AB+'],
        ];

        return in_array($recipientBlood, $compatibility[$donorBlood] ?? [], true);
    }
}
