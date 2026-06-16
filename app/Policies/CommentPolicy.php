<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isModerator() || $user->isAdmin();
    }

    public function flag(User $user): bool
    {
        return $user->isModerator() || $user->isAdmin();
    }

    public function unflag(User $user): bool
    {
        return $user->isModerator() || $user->isAdmin();
    }
}
