<?php

namespace App\Http\Controllers;

use App\Http\Requests\NearbyBloodRequestRequest;
use App\Http\Requests\StoreBloodRequestRequest;
use App\Http\Resources\BloodRequestResource;
use App\Http\Resources\DonationResource;
use App\Models\BloodRequest;
use App\Services\BloodRequestService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;

class BloodRequestController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly BloodRequestService $service,
    ) {}

    /**
     * Membuat permohonan darah baru (dengan upload bukti medis).
     */
    public function store(StoreBloodRequestRequest $request): JsonResponse
    {
        $bloodRequest = $this->service->create(
            $request->user(),
            $request->validated(),
            $request->file('medical_reference_proof'),
        );

        return $this->success(
            'Permohonan darah berhasil dibuat.',
            new BloodRequestResource($bloodRequest),
            201
        );
    }

    /**
     * Menampilkan daftar permohonan darah terdekat (geo-matching Haversine).
     */
    public function nearby(NearbyBloodRequestRequest $request): JsonResponse
    {
        $items = $this->service->nearby(
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            (float) ($request->input('radius_km') ?? 25),
            $request->input('blood_type'),
        );

        $perPage = (int) $request->input('per_page', 15);
        $page = max((int) $request->input('page', 1), 1);

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $data = $paginator->getCollection()->map(
            fn (array $item) => new BloodRequestResource($item['blood_request'], $item['distance_km'])
        );

        return $this->success(
            'Permohonan darah terdekat.',
            $data,
            meta: $this->paginationMeta($paginator),
        );
    }

    /**
     * Konfirmasi pendonor siap membantu sebuah permohonan darah.
     */
    public function confirm(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        try {
            $donation = $this->service->confirm($request->user(), $bloodRequest);

            return $this->success(
                'Konfirmasi donor berhasil. Tiket donor telah dibuat.',
                new DonationResource($donation),
                201
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Membatalkan permohonan darah oleh pemiliknya.
     */
    public function cancel(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        try {
            $cancelled = $this->service->cancel($request->user(), $bloodRequest);

            return $this->success(
                'Permohonan darah dibatalkan.',
                new BloodRequestResource($cancelled)
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
