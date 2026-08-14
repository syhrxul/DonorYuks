<?php

namespace App\Policies;

use App\Models\BloodRequest;
use App\Models\User;

class BloodRequestPolicy
{
    /**
     * Pendonor (selain pemohon) boleh mengonfirmasi permohonan yang masih open.
     */
    public function confirm(User $user, BloodRequest $bloodRequest): bool
    {
        return $bloodRequest->status === 'open' && $user->id !== $bloodRequest->user_id;
    }

    /**
     * Hanya pemohon yang boleh membatalkan permohonannya sendiri.
     */
    public function cancel(User $user, BloodRequest $bloodRequest): bool
    {
        return $bloodRequest->user_id === $user->id;
    }
}
