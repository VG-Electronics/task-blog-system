<?php

return [
    \App\Services\Config\RiskAssessmentConfig::DEFAULT_RISK_SCORE => 20,
    \App\Services\Config\RiskAssessmentConfig::SHORT_CONTENT_SCORE => 10,
    \App\Services\Config\RiskAssessmentConfig::KEYWORDS_SCORE => 5,
    \App\Services\Config\RiskAssessmentConfig::SHORT_CONTENT_MAX_LENGTH => 49,

    \App\Services\Config\RiskAssessmentConfig::HIGH_RISK_SCORE_THRESHOLD => 71,
    \App\Services\Config\RiskAssessmentConfig::MEDIUM_RISK_SCORE_THRESHOLD => 30,
    \App\Services\Config\RiskAssessmentConfig::KEYWORDS => [
        'fire', 'accident', 'theft', 'damage', 'burglary',
    ],
];
