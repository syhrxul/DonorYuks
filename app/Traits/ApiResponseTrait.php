<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Trait untuk menyeragamkan format respons API DonorYuks.
 *
 * Format sukses:
 * { "status": "success", "message": "...", "data": {} }
 *
 * Format sukses ber-pagination:
 * { "status": "success", "message": "...", "data": [], "meta": {...} }
 *
 * Format error:
 * { "status": "error", "message": "...", "errors": {} }
 */
trait ApiResponseTrait
{
    /**
     * Mengembalikan respons sukses dengan status HTTP 2xx.
     */
    protected function success(string $message, mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['status' => 'success', 'message' => $message];

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Mengembalikan respons error dengan status HTTP 4xx/5xx.
     */
    protected function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        $payload = ['status' => 'error', 'message' => $message];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Membuat struktur meta pagination dari paginator Laravel.
     *
     * @return array{current_page: int, per_page: int, last_page: int, total: int}
     */
    protected function paginationMeta(LengthAwarePaginator|Paginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}
