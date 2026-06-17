<?php

namespace App\Services;

use App\Exceptions\PostArchivedException;
use App\Jobs\CalculatePostRisk;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    public function getAll(): Collection
    {
        return Post::active()->with('user:id,nickname')->withCount(['comments' => fn($q) => $q->where('flag', false)])->get();
    }

    public function getById(Post $post): Post
    {
        if ($post->isArchived()) {
            throw new PostArchivedException();
        }

        return $post->load([
            'user:id,nickname',
            'comments' => fn($q) => $q->where('flag', false)->with('user:id,nickname'),
        ]);
    }

    public function create(array $data): Post
    {
        $post = Post::create($data);

        CalculatePostRisk::dispatch($post);

        return $post;
    }

    public function update(Post $post, array $data): Post
    {
        if ($post->isArchived()) {
            throw new PostArchivedException();
        }

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
