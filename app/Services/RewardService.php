<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\User;
use App\Models\UserReward;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Layanan bisnis modul gamifikasi & reward.
 */
class RewardService
{
    /**
     * Menampilkan daftar reward yang masih tersedia stoknya.
     */
    public function list(): Collection
    {
        return Reward::query()
            ->where('stock', '>', 0)
            ->orderBy('points_required')
            ->get();
    }

    /**
     * Menukar poin pengguna dengan reward.
     *
     * @throws RuntimeException
     */
    public function redeem(User $user, Reward $reward): UserReward
    {
        if ($reward->stock < 1) {
            throw new RuntimeException('Stok reward ini sudah habis.');
        }

        if ($user->points < $reward->points_required) {
            throw new RuntimeException('Poin Anda tidak mencukupi untuk reward ini.');
        }

        return DB::transaction(function () use ($user, $reward) {
            $reward->decrement('stock');
            $user->decrement('points', $reward->points_required);

            return UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'claim_code' => 'RWD-'.strtoupper(Str::random(10)),
                'status' => 'claimed',
            ]);
        });
    }
}
