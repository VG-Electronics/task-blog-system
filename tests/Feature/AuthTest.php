<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function registerUser(
        string $nickname = 'testuser',
        string $email = 'test@example.com',
        string $password = 'password123',
    ): array {
        $response = $this->postJson('/api/register', [
            'nickname'              => $nickname,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
        ]);

        return [
            'token' => $response->json('token'),
            'user'  => User::where('email', $email)->first(),
        ];
    }

    public function test_user_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/register', [
            'nickname'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['token']);
    }

    public function test_registered_user_has_user_role(): void
    {
        ['user' => $user] = $this->registerUser();

        $this->assertTrue($user->roles->contains('name', Role::USER));
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $this->registerUser();

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token']);
    }

    public function test_logged_in_user_can_create_post(): void
    {
        ['token' => $token] = $this->registerUser();

        $this->withToken($token)
            ->postJson('/api/posts', [
                'title'       => 'Test Post',
                'description' => 'Test Description',
                'content'     => 'Test Content',
            ])
            ->assertStatus(201);
    }

    public function test_guest_cannot_create_post(): void
    {
        $this->postJson('/api/posts', [
            'title'       => 'Test Post',
            'description' => 'Test Description',
            'content'     => 'Test Content',
        ])->assertStatus(401);
    }

    public function test_cannot_login_with_wrong_password(): void
    {
        $this->registerUser();

        $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401)->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_cannot_login_with_nonexistent_email(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ])->assertStatus(401);
    }

    public function test_user_cannot_access_admin_reporting_posts(): void
    {
        ['token' => $token] = $this->registerUser();

        $this->withToken($token)->getJson('/api/admin/reporting/posts')->assertStatus(403);
    }

    public function test_user_cannot_access_admin_reporting_comments(): void
    {
        ['token' => $token] = $this->registerUser();

        $this->withToken($token)->getJson('/api/admin/reporting/comments')->assertStatus(403);
    }

    public function test_user_cannot_access_admin_reporting_analytics(): void
    {
        ['token' => $token] = $this->registerUser();

        $this->withToken($token)->getJson('/api/admin/reporting/analytics')->assertStatus(403);
    }

    public function test_user_cannot_flag_comment(): void
    {
        ['token' => $token, 'user' => $user] = $this->registerUser();

        $post    = Post::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(403);
    }

    public function test_user_cannot_unflag_comment(): void
    {
        ['token' => $token, 'user' => $user] = $this->registerUser();

        $post    = Post::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}/flag")
            ->assertStatus(403);
    }

    public function test_admin_can_login_and_access_reporting_endpoints(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', Role::ADMIN)->first());

        $loginResponse = $this->postJson('/api/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)->assertJsonStructure(['token']);
        $token = $loginResponse->json('token');

        $this->withToken($token)->getJson('/api/admin/reporting/posts')->assertStatus(200);
        $this->withToken($token)->getJson('/api/admin/reporting/comments')->assertStatus(200);
        $this->withToken($token)->getJson('/api/admin/reporting/analytics')->assertStatus(200);
    }

    public function test_admin_has_admin_role(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', Role::ADMIN)->first());

        $this->assertTrue($admin->isAdmin());
    }
}
