<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', Role::USER))->get();
        $tags = Tag::all();

        $posts = $users->map(fn(User $u) => Post::factory()->create(['user_id' => $u->id]));

        $posts[0]->tags()->attach($tags[0]);
        $posts[1]->tags()->attach($tags[0]);
        $posts[2]->tags()->attach($tags[1]);
    }
}
