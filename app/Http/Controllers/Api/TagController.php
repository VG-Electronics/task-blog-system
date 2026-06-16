<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Post;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

class TagController extends ApiController
{
    public function __construct(private TagService $tagService) {}

    public function assign(Post $post, string $tag): JsonResponse
    {
        $this->authorize('assign', [Tag::class, $post]);

        $this->tagService->assign($post, $tag);

        return response()->json(null, 204);
    }

    public function unassign(Post $post, string $tag): JsonResponse
    {
        $this->authorize('unassign', [Tag::class, $post]);

        $this->tagService->unassign($post, $tag);

        return response()->json(null, 204);
    }
}
