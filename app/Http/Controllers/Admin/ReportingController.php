<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\AdminReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingController extends ApiController
{
    public function __construct(private AdminReportingService $service) {}

    public function posts(Request $request): JsonResponse|StreamedResponse
    {
        $query = Post::with(['user', 'tags'])
            ->withCount('comments')
            ->orderBy(
                $this->service->resolveSort($request),
                $this->service->resolveSortDir($request)
            );

        $this->service->applyDateRange($query, $request);
        $this->service->applyPostFilters($query, $request);

        $format = $this->service->resolveFormat($request);

        if ($format === 'json') {
            return response()->json($query->paginate($request->integer('per_page', 15)));
        }

        $headers = ['ID', 'Title', 'Description', 'Risk Level', 'Risk Score', 'Comments', 'Tags', 'Author', 'Created At', 'Updated At'];
        $rows = $query->get()->map(fn(Post $post) => [
            $post->id,
            $post->title,
            $post->description,
            $post->risk_level?->value,
            $post->risk_score,
            $post->comments_count,
            $post->tags->pluck('tag')->join(', '),
            $post->user->nickname,
            $post->created_at,
            $post->updated_at,
        ])->toArray();

        return $format === 'csv'
            ? $this->service->respondAsCsv($headers, $rows, 'posts.csv')
            : $this->service->respondAsXls($headers, $rows, 'posts.xlsx');
    }

    public function comments(Request $request): JsonResponse|StreamedResponse
    {
        $query = Comment::with(['user', 'post'])
            ->orderBy(
                $this->service->resolveSort($request),
                $this->service->resolveSortDir($request)
            );

        $this->service->applyDateRange($query, $request);
        $this->service->applyCommentFilters($query, $request);

        $format = $this->service->resolveFormat($request);

        if ($format === 'json') {
            return response()->json($query->paginate($request->integer('per_page', 15)));
        }

        $headers = ['ID', 'Content', 'Flag', 'Author', 'Post', 'Created At', 'Updated At'];
        $rows = $query->get()->map(fn(Comment $comment) => [
            $comment->id,
            $comment->content,
            $comment->flag ? 'Yes' : 'No',
            $comment->user->nickname,
            $comment->post->title,
            $comment->created_at,
            $comment->updated_at,
        ])->toArray();

        return $format === 'csv'
            ? $this->service->respondAsCsv($headers, $rows, 'comments.csv')
            : $this->service->respondAsXls($headers, $rows, 'comments.xlsx');
    }

    public function analytics(): JsonResponse
    {
        $totalPosts = Post::count();
        $totalComments = Comment::count();

        $topUsers = User::withCount(['posts', 'comments'])
            ->orderByRaw('posts_count + comments_count DESC')
            ->limit(10)
            ->get(['id', 'nickname', 'email']);

        $mostCommonTags = Tag::withCount('posts')
            ->has('posts')
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get(['id', 'tag', 'posts_count']);

        return response()->json([
            'total_posts' => $totalPosts,
            'total_comments' => $totalComments,
            'avg_comments_per_post' => $totalPosts > 0
                ? round($totalComments / $totalPosts, 2)
                : 0,
            'top_users' => $topUsers,
            'most_common_tags' => $mostCommonTags,
        ]);
    }
}
