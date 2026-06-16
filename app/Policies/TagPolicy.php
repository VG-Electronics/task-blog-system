<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function assign(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isModerator() || $user->isAdmin();
    }

    public function unassign(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isModerator() || $user->isAdmin();
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->isModerator() || $user->isAdmin();
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->isModerator() || $user->isAdmin();
    }
}
