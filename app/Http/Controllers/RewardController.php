<?php

namespace App\Http\Controllers;

use App\Http\Resources\RewardResource;
use App\Models\Reward;
use App\Services\RewardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RewardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly RewardService $service,
    ) {}

    /**
     * Menampilkan daftar reward yang tersedia untuk ditukar poin.
     */
    public function index(): JsonResponse
    {
        return $this->success('Daftar reward.', RewardResource::collection($this->service->list()));
    }

    /**
     * Menukar poin pengguna dengan reward.
     */
    public function redeem(Request $request, Reward $reward): JsonResponse
    {
        try {
            $userReward = $this->service->redeem($request->user(), $reward);

            return $this->success(
                'Reward berhasil ditukar.',
                [
                    'id' => $userReward->id,
                    'claim_code' => $userReward->claim_code,
                    'status' => $userReward->status,
                    'reward' => new RewardResource($userReward->reward),
                    'remaining_points' => $request->user()->fresh()->points,
                ],
                201
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
