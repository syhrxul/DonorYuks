<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexEventRequest;
use App\Http\Resources\DonationResource;
use App\Http\Resources\DonorEventResource;
use App\Models\DonorEvent;
use App\Services\EventService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

class DonorEventController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly EventService $service,
    ) {}

    /**
     * Menampilkan daftar event donor terdekat (pageable) dengan filter tanggal & lokasi.
     */
    public function index(IndexEventRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $paginator = $this->service->list(
            $request->filled('latitude') ? (float) $request->input('latitude') : null,
            $request->filled('longitude') ? (float) $request->input('longitude') : null,
            $request->filled('radius_km') ? (float) $request->input('radius_km') : null,
            $request->input('date'),
            $perPage,
        );

        $data = Collection::make($paginator->items())->map(
            fn (array $item) => new DonorEventResource($item['event'], $item['distance_km'])
        );

        return $this->success(
            'Daftar event donor.',
            $data,
            meta: $this->paginationMeta($paginator),
        );
    }

    /**
     * Booking pendaftaran event donor (menghasilkan tiket/QR code).
     */
    public function book(Request $request, DonorEvent $donorEvent): JsonResponse
    {
        try {
            $donation = $this->service->book($request->user(), $donorEvent);

            return $this->success(
                'Booking event donor berhasil.',
                new DonationResource($donation),
                201
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
