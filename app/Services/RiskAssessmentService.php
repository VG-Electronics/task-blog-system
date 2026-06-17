<?php

namespace App\Services;

use App\Enums\PostRiskLevel;
use App\Models\Post;
use App\Services\Config\RiskAssessmentConfig;

class RiskAssessmentService
{
    public function __construct(private RiskAssessmentConfig $config)
    {
    }

    public function getPostRiskLevel(int $score): PostRiskLevel
    {
        return $this->config->getRiskScoreLevel($score);
    }

    public function calculatePostRiskScore(Post $post): int
    {
        $riskScore = $this->config->getDefaultRiskScore();

        if (strlen($post->content) <= $this->config->getShortContentMaxLength()) {
            $riskScore += $this->config->getShortContentScore();
        }

        if ($this->containKeywords($post)) {
            $riskScore += $this->config->getKeywordsScore();
        }

        return $riskScore;
    }

    protected function containKeywords(Post $post): bool
    {
        $keywords = array_map(fn($e) => strtolower($e), $this->config->getKeywords());

        foreach ([$post->title, $post->description, $post->content] as $field) {
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($field), $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }
}
