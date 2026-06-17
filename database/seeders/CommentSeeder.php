<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', Role::USER))
            ->with('posts')
            ->get();

        foreach ($users as $i => $user) {
            $targetUser = $users[($i + 1) % $users->count()];
            $post = $targetUser->posts->first();

            Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        }
    }
}
