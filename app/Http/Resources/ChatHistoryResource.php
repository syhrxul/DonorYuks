<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatHistoryResource extends JsonResource
{
    /**
     * Memformat data riwayat percakapan Bloody ke struktur JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'answer' => $this->answer,
            'provider' => $this->provider,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
