<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocationRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\DonationResource;
use App\Http\Resources\DonorCardResource;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * Menampilkan profil pengguna yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success('Data profil.', new UserResource($request->user()));
    }

    /**
     * Memperbarui profil pengguna (nama, email, no hp, golongan darah, dll).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->validated());

        return $this->success('Profil berhasil diperbarui.', new UserResource($user->fresh()));
    }

    /**
     * Memperbarui lokasi real-time (latitude/longitude) pengguna.
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->only(['latitude', 'longitude']));

        return $this->success('Lokasi berhasil diperbarui.', [
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ]);
    }

    /**
     * Menampilkan Kartu Donor Digital beserta statistik riwayat donor.
     */
    public function donorCard(Request $request): JsonResponse
    {
        $user = $request->user()->load('donations');

        return $this->success('Kartu donor digital.', new DonorCardResource($user));
    }

    /**
     * Menampilkan riwayat donor (track record) pengguna.
     */
    public function trackRecord(Request $request): JsonResponse
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);

        $donations = $request->user()
            ->donations()
            ->with(['bloodRequest', 'donorEvent'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return $this->success(
            'Riwayat donor.',
            DonationResource::collection($donations->items()),
            meta: $this->paginationMeta($donations),
        );
    }
}
