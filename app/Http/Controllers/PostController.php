<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    use ApiResponseTrait;

    /**
     * Menampilkan daftar artikel edukasi & berita seputar donor darah.
     * Mendukung pagination, filter kategori, dan pencarian judul.
     */
    public function index(IndexPostRequest $request): JsonResponse
    {
        $posts = Post::published()
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->input('q').'%'))
            ->orderByDesc('published_at')
            ->paginate((int) $request->input('per_page', 15));

        return $this->success(
            'Daftar artikel edukasi & berita.',
            PostResource::collection($posts->items()),
            meta: $this->paginationMeta($posts),
        );
    }
}
