<?php

namespace Tests\Feature;

use App\Enums\PostRiskLevel;
use App\Jobs\CalculatePostRisk;
use App\Jobs\NotifyAboutHighRiskPost;
use App\Models\Post;
use App\Models\User;
use App\Services\RiskAssessmentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostCrudTest extends TestCase
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

    public function test_authenticated_user_can_create_post(): void
    {
        Queue::fake();
        $user  = User::factory()->create();
        $token = $this->loginUser($user);

        $this->withToken($token)
            ->postJson('/api/posts', [
                'title'       => 'My Post',
                'description' => 'A description',
                'content'     => 'Some content',
            ])
            ->assertStatus(201);
    }

    public function test_authenticated_user_can_update_own_post(): void
    {
        Queue::fake();
        $user  = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $user->id]);
        $token = $this->loginUser($user);

        $this->withToken($token)
            ->putJson("/api/posts/{$post->id}", [
                'title'       => 'Updated Title',
                'description' => 'Updated description',
                'content'     => 'Updated content',
            ])
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_delete_own_post(): void
    {
        Queue::fake();
        $user  = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $user->id]);
        $token = $this->loginUser($user);

        $this->withToken($token)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertStatus(204);
    }

    public function test_anyone_can_read_someone_elses_post(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->id}")->assertStatus(200);
    }

    public function test_user_cannot_update_someone_elses_post(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $owner->id]);
        $token = $this->loginUser($other);

        $this->withToken($token)
            ->putJson("/api/posts/{$post->id}", [
                'title'       => 'Hacked Title',
                'description' => 'Hacked description',
                'content'     => 'Hacked content',
            ])
            ->assertStatus(403);
    }

    public function test_cannot_create_post_with_missing_data(): void
    {
        $user  = User::factory()->create();
        $token = $this->loginUser($user);

        $this->withToken($token)
            ->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'content']);
    }

    public function test_calculate_post_risk_job_is_dispatched_after_creating_post(): void
    {
        Queue::fake();
        $user  = User::factory()->create();
        $token = $this->loginUser($user);

        $this->withToken($token)->postJson('/api/posts', [
            'title'       => 'My Post',
            'description' => 'A description',
            'content'     => 'Some content',
        ]);

        Queue::assertPushed(CalculatePostRisk::class);
    }

    public function test_calculate_post_risk_sets_risk_score_and_risk_level(): void
    {
        Queue::fake();
        $post = Post::factory()->create();

        (new CalculatePostRisk($post))->handle(app(RiskAssessmentService::class));

        $post->refresh();

        $this->assertNotNull($post->risk_score);
        $this->assertNotNull($post->risk_level);
        $this->assertInstanceOf(PostRiskLevel::class, $post->risk_level);
    }

    public function test_high_risk_post_sends_notification_to_admin(): void
    {
        Queue::fake();
        $post    = Post::factory()->create();
        $service = $this->mock(RiskAssessmentService::class);
        $service->expects('calculatePostRiskScore')->with($post)->andReturn(100);
        $service->expects('getPostRiskLevel')->with(100)->andReturn(PostRiskLevel::HIGH);

        (new CalculatePostRisk($post))->handle($service);

        Queue::assertPushed(NotifyAboutHighRiskPost::class, fn($job) => $job->post->id === $post->id);
    }
}
