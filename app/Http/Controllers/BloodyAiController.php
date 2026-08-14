<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloodyChatRequest;
use App\Http\Resources\ChatHistoryResource;
use App\Services\BloodyAiService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloodyAiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly BloodyAiService $service,
    ) {}

    /**
     * Menghubungkan pertanyaan pengguna ke AI Chatbot "Bloody".
     *
     * Mengirimkan message pengguna ke Service AI (OpenAI/Gemini) beserta
     * System Prompt persona Bloody, lalu mengembalikan jawaban edukatif.
     * Percakapan tersimpan sebagai riwayat.
     */
    public function chat(BloodyChatRequest $request): JsonResponse
    {
        $history = $this->service->chat($request->user(), $request->validated('message'));

        return $this->success('Jawaban Bloody.', [
            'answer' => $history->answer,
            'history_id' => $history->id,
        ]);
    }

    /**
     * Menampilkan riwayat percakapan dengan Bloody milik pengguna.
     */
    public function history(Request $request): JsonResponse
    {
        $histories = $this->service->history($request->user());

        return $this->success(
            'Riwayat percakapan Bloody.',
            ChatHistoryResource::collection($histories->items()),
            meta: $this->paginationMeta($histories),
        );
    }
}
