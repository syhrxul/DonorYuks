<?php

namespace App\Http\Controllers;

use App\Http\Resources\DonationResource;
use App\Models\Donation;
use App\Services\DonationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DonationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly DonationService $service,
    ) {}

    /**
     * Memverifikasi donasi telah berhasil diselesaikan.
     * Memberikan poin ke pendonor & mengupdate jumlah kantong terpenuhi.
     */
    public function complete(Request $request, Donation $donation): JsonResponse
    {
        try {
            $result = $this->service->complete($request->user(), $donation);

            return $this->success(
                'Donasi berhasil diselesaikan.',
                [
                    'donation' => new DonationResource($result['donation']),
                    'points_earned' => $result['points_earned'],
                ]
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Membatalkan donasi oleh pendonor yang belum terselesaikan.
     */
    public function cancel(Request $request, Donation $donation): JsonResponse
    {
        try {
            $cancelled = $this->service->cancel($request->user(), $donation);

            return $this->success(
                'Donasi dibatalkan.',
                new DonationResource($cancelled)
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
