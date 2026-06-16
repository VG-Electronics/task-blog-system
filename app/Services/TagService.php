<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    public function getAll(): Collection
    {
        return Tag::all();
    }

    public function getById(Tag $tag): Tag
    {
        return $tag;
    }

    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);

        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }

    public function assign(Post $post, string $tagName): void
    {
        $tag = Tag::firstOrCreate(['tag' => $tagName]);
        $post->tags()->syncWithoutDetaching($tag->id);
    }

    public function unassign(Post $post, string $tagName): void
    {
        $tag = Tag::where('tag', $tagName)->first();
        if ($tag) {
            $post->tags()->detach($tag->id);
        }
    }
}
