<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

class DonationPolicy
{
    /**
     * Hanya pendonor pemilik donasi yang boleh menyelesaikannya.
     */
    public function complete(User $user, Donation $donation): bool
    {
        return $donation->donor_id === $user->id;
    }

    /**
     * Hanya pendonor pemilik donasi yang boleh membatalkannya.
     */
    public function cancel(User $user, Donation $donation): bool
    {
        return $donation->donor_id === $user->id;
    }
}
