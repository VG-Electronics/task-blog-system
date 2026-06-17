<?php

namespace Tests\Unit;

use App\Enums\PostRiskLevel;
use App\Models\Post;
use App\Services\Config\RiskAssessmentConfig;
use App\Services\RiskAssessmentService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RiskAssessmentServiceTest extends TestCase
{
    private RiskAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('post_risk', [
            RiskAssessmentConfig::DEFAULT_RISK_SCORE          => 10,
            RiskAssessmentConfig::SHORT_CONTENT_SCORE         => 20,
            RiskAssessmentConfig::KEYWORDS_SCORE              => 30,
            RiskAssessmentConfig::SHORT_CONTENT_MAX_LENGTH    => 50,
            RiskAssessmentConfig::MEDIUM_RISK_SCORE_THRESHOLD => 25,
            RiskAssessmentConfig::HIGH_RISK_SCORE_THRESHOLD   => 55,
            RiskAssessmentConfig::KEYWORDS                    => ['spam', 'malware'],
        ]);

        $this->service = app(RiskAssessmentService::class);
    }

    private function makePost(string $title, string $description, string $content): Post
    {
        return new Post(compact('title', 'description', 'content'));
    }

    public function test_long_content_no_keywords_gives_default_score_and_low_level(): void
    {
        $post  = $this->makePost('Normal title', 'Normal description', str_repeat('a', 51));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(10, $score);
        $this->assertSame(PostRiskLevel::LOW, $this->service->getPostRiskLevel($score));
    }

    public function test_short_content_no_keywords_adds_short_content_score_and_gives_medium_level(): void
    {
        $post  = $this->makePost('Normal title', 'Normal description', str_repeat('a', 50));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(30, $score); // 10 + 20
        $this->assertSame(PostRiskLevel::MEDIUM, $this->service->getPostRiskLevel($score));
    }

    public function test_short_content_with_keyword_in_title_gives_high_level(): void
    {
        $post  = $this->makePost('spam', 'Normal description', str_repeat('a', 50));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(60, $score); // 10 + 20 + 30
        $this->assertSame(PostRiskLevel::HIGH, $this->service->getPostRiskLevel($score));
    }

    public function test_long_content_with_keyword_in_title_gives_medium_level(): void
    {
        $post  = $this->makePost('malware', 'Normal description', str_repeat('a', 51));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(40, $score); // 10 + 30
        $this->assertSame(PostRiskLevel::MEDIUM, $this->service->getPostRiskLevel($score));
    }

    public function test_keyword_match_is_case_insensitive(): void
    {
        $post  = $this->makePost('SPAM', 'Normal description', str_repeat('a', 51));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(40, $score); // 10 + 30
    }

    public function test_keyword_in_description_triggers_keywords_score(): void
    {
        $post  = $this->makePost('Normal title', 'malware', str_repeat('a', 51));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(40, $score); // 10 + 30
    }

    public function test_keyword_in_content_triggers_keywords_score_and_short_content_score(): void
    {
        $post  = $this->makePost('Normal title', 'Normal description', 'spam');
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(60, $score); // 10 + 20 + 30 — 'spam' is also short content
        $this->assertSame(PostRiskLevel::HIGH, $this->service->getPostRiskLevel($score));
    }

    public function test_keyword_embedded_in_text_triggers_keywords_score(): void
    {
        $post  = $this->makePost('this is spam content', 'Normal description', str_repeat('a', 51));
        $score = $this->service->calculatePostRiskScore($post);

        $this->assertSame(40, $score); // 10 + 30
    }

    public function test_score_at_medium_threshold_gives_medium_level(): void
    {
        $this->assertSame(PostRiskLevel::MEDIUM, $this->service->getPostRiskLevel(25));
    }

    public function test_score_at_high_threshold_gives_high_level(): void
    {
        $this->assertSame(PostRiskLevel::HIGH, $this->service->getPostRiskLevel(55));
    }

    public function test_score_below_medium_threshold_gives_low_level(): void
    {
        $this->assertSame(PostRiskLevel::LOW, $this->service->getPostRiskLevel(24));
    }
}
