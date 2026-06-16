<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class PostController extends ApiController
{
    public function __construct(private PostService $postService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->postService->getAll());
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($this->postService->getById($post));
    }

    public function store(PostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        return response()->json($this->postService->create($request->validated()), 201);
    }

    public function update(PostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        return response()->json($this->postService->update($post, $request->validated()));
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $this->postService->delete($post);

        return response()->json(null, 204);
    }
}
