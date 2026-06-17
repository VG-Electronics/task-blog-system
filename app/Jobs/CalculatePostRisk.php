<?php

namespace App\Jobs;

use App\Enums\PostRiskLevel;
use App\Models\Post;
use App\Services\RiskAssessmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculatePostRisk implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Post $post) {}

    public function handle(RiskAssessmentService $service): void
    {
        $riskScore = $service->calculatePostRiskScore($this->post);
        $riskLevel = $service->getPostRiskLevel($riskScore);

        $this->post->update([
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
        ]);

        if ($this->post->risk_level === PostRiskLevel::HIGH) {
            NotifyAboutHighRiskPost::dispatch($this->post);
        }
    }
}
