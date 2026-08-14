<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'reward_id',
    'claim_code',
    'status',
])]
class UserReward extends Model
{
    /**
     * Pengguna yang mengklaim reward.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reward yang diklaim.
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}
