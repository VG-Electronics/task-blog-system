<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    public function getAll(Post $post): Collection
    {
        return $post->comments;
    }

    public function getById(Comment $comment): Comment
    {
        return $comment;
    }

    public function create(Post $post, array $data): Comment
    {
        return $post->comments()->create($data);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update($data);

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }

    public function flag(Comment $comment): void
    {
        $comment->update(['flag' => true]);
    }

    public function unflag(Comment $comment): void
    {
        $comment->update(['flag' => false]);
    }
}
