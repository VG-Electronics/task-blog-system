<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function loginUser(User $user): string
    {
        return $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('token');
    }

    private function createUserWithRole(string $role): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role)->first());

        return [$user, $this->loginUser($user)];
    }

    public function test_anyone_can_read_comments_on_a_post(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id]);

        $this->getJson("/api/posts/{$post->id}/comments")->assertStatus(200);
    }

    public function test_authenticated_user_can_add_comment_to_post(): void
    {
        $user  = User::factory()->create();
        $post  = Post::factory()->create();
        $token = $this->loginUser($user);

        $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments", ['content' => 'Great post!'])
            ->assertStatus(201)
            ->assertJsonFragment(['content' => 'Great post!']);
    }

    public function test_guest_cannot_create_comment(): void
    {
        $post = Post::factory()->create();

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Hello'])
            ->assertStatus(401);
    }

    public function test_cannot_create_comment_with_missing_content(): void
    {
        $user  = User::factory()->create();
        $post  = Post::factory()->create();
        $token = $this->loginUser($user);

        $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_authenticated_user_can_edit_own_comment(): void
    {
        $user    = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        $token   = $this->loginUser($user);

        $this->withToken($token)
            ->putJson("/api/posts/{$post->id}/comments/{$comment->id}", ['content' => 'Edited content'])
            ->assertStatus(200)
            ->assertJsonFragment(['content' => 'Edited content']);
    }

    public function test_user_cannot_edit_someone_elses_comment(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);
        $token   = $this->loginUser($other);

        $this->withToken($token)
            ->putJson("/api/posts/{$post->id}/comments/{$comment->id}", ['content' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_admin_can_edit_someone_elses_comment(): void
    {
        $owner   = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);

        [, $token] = $this->createUserWithRole(Role::ADMIN);

        $this->withToken($token)
            ->putJson("/api/posts/{$post->id}/comments/{$comment->id}", ['content' => 'Admin edit'])
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_delete_own_comment(): void
    {
        $user    = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        $token   = $this->loginUser($user);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_someone_elses_comment(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);
        $token   = $this->loginUser($other);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}")
            ->assertStatus(403);
    }

    public function test_moderator_can_delete_someone_elses_comment(): void
    {
        $owner   = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);

        [, $token] = $this->createUserWithRole(Role::MODERATOR);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}")
            ->assertStatus(204);
    }

    public function test_user_cannot_flag_comment(): void
    {
        $user    = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $token   = $this->loginUser($user);

        $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(403);
    }

    public function test_moderator_can_flag_comment(): void
    {
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        [, $token] = $this->createUserWithRole(Role::MODERATOR);

        $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(204);

        $this->assertTrue((bool) $comment->fresh()->flag);
    }

    public function test_admin_can_flag_comment(): void
    {
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        [, $token] = $this->createUserWithRole(Role::ADMIN);

        $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(204);

        $this->assertTrue((bool) $comment->fresh()->flag);
    }

    public function test_user_cannot_unflag_comment(): void
    {
        $user    = User::factory()->create();
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'flag' => true]);
        $token   = $this->loginUser($user);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(403);
    }

    public function test_moderator_can_unflag_comment(): void
    {
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'flag' => true]);

        [, $token] = $this->createUserWithRole(Role::MODERATOR);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(204);

        $this->assertFalse((bool) $comment->fresh()->flag);
    }

    public function test_admin_can_unflag_comment(): void
    {
        $post    = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'flag' => true]);

        [, $token] = $this->createUserWithRole(Role::ADMIN);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(204);

        $this->assertFalse((bool) $comment->fresh()->flag);
    }
}
