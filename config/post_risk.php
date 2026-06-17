<?php

return [
    \App\Services\RiskAssessmentConfig::DEFAULT_RISK_SCORE => 20,
    \App\Services\RiskAssessmentConfig::SHORT_CONTENT_SCORE => 10,
    \App\Services\RiskAssessmentConfig::KEYWORDS_SCORE => 5,
    \App\Services\RiskAssessmentConfig::SHORT_CONTENT_MAX_LENGTH => 49,

    \App\Services\RiskAssessmentConfig::HIGH_RISK_SCORE_THRESHOLD => 71,
    \App\Services\RiskAssessmentConfig::MEDIUM_RISK_SCORE_THRESHOLD => 30,
    \App\Services\RiskAssessmentConfig::KEYWORDS => [
        'fire', 'accident', 'theft', 'damage', 'burglary',
    ],
];
