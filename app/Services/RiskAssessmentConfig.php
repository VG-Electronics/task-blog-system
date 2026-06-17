<?php

namespace App\Services;

use App\Enums\PostRiskLevel;

class RiskAssessmentConfig
{
    const string DEFAULT_RISK_SCORE = 'default_risk_score';
    const string SHORT_CONTENT_SCORE = 'short_content_score';
    const string KEYWORDS_SCORE = 'keywords_score';
    const string SHORT_CONTENT_MAX_LENGTH = 'short_content_length';
    const string HIGH_RISK_SCORE_THRESHOLD = 'risk_score_threshold';
    const string MEDIUM_RISK_SCORE_THRESHOLD = 'medium_risk_score_threshold';
    const string KEYWORDS = 'keywords';

    public function getRiskScoreLevel(int $score): PostRiskLevel
    {
        $highRiskThreshold = config('post_risk.' . self::HIGH_RISK_SCORE_THRESHOLD, 0);
        $mediumRiskThreshold = config('post_risk.' . self::MEDIUM_RISK_SCORE_THRESHOLD, 0);

        if ($score >= $highRiskThreshold) {
            return PostRiskLevel::HIGH;
        } elseif ($score >= $mediumRiskThreshold) {
            return PostRiskLevel::MEDIUM;
        }

        return PostRiskLevel::LOW;
    }

    public function getDefaultRiskScore(): int
    {
        return $this->getConfigValue(self::DEFAULT_RISK_SCORE, 0);
    }

    public function getShortContentScore(): int
    {
        return $this->getConfigValue(self::SHORT_CONTENT_SCORE, 0);
    }

    public function getKeywordsScore(): int
    {
        return $this->getConfigValue(self::KEYWORDS_SCORE, 0);
    }

    public function getShortContentMaxLength(): int
    {
        return $this->getConfigValue(self::SHORT_CONTENT_MAX_LENGTH, 0);
    }

    public function getKeywords(): array
    {
        return $this->getConfigValue(self::KEYWORDS, []);
    }

    private function getConfigValue(string $key, mixed $default): mixed
    {
        return config("post_risk.$key", $default);
    }
}
