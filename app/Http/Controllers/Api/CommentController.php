<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;

class CommentController extends ApiController
{
    public function __construct(private CommentService $commentService) {}

    public function index(Post $post): JsonResponse
    {
        return response()->json($this->commentService->getAll($post));
    }

    public function show(Post $post, Comment $comment): JsonResponse
    {
        return response()->json($this->commentService->getById($comment));
    }

    public function store(CommentRequest $request, Post $post): JsonResponse
    {
        $this->authorize('create', Comment::class);

        return response()->json(
            $this->commentService->create($post, [...$request->validated(), 'user_id' => $request->user()->id]),
            201,
        );
    }

    public function update(CommentRequest $request, Post $post, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        return response()->json($this->commentService->update($comment, $request->validated()));
    }

    public function destroy(Post $post, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->delete($comment);

        return response()->json(null, 204);
    }

    public function flag(Post $post, Comment $comment): JsonResponse
    {
        $this->authorize('flag', Comment::class);

        $this->commentService->flag($comment);

        return response()->json(null, 204);
    }

    public function unflag(Post $post, Comment $comment): JsonResponse
    {
        $this->authorize('unflag', Comment::class);

        $this->commentService->unflag($comment);

        return response()->json(null, 204);
    }
}
