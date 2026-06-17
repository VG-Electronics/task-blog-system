<?php

namespace App\Services;

use App\Jobs\CalculatePostRisk;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    public function getAll(): Collection
    {
        return Post::with('user:id,nickname')->withCount('comments')->get();
    }

    public function getById(Post $post): Post
    {
        return $post->loadMissing(['user:id,nickname', 'comments.user:id,nickname']);
    }

    public function create(array $data): Post
    {
        $post = Post::create($data);

        CalculatePostRisk::dispatch($post);

        return $post;
    }

    public function update(Post $post, array $data): Post
    {
        $post->update(array_merge($data, [
            // Clear previous score
            'risk_score' => null,
            'risk_level' => null,
        ]));

        CalculatePostRisk::dispatch($post);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
