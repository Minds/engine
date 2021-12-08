<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they feel like they part of either a narrow or wide echo chamber
 */
class DebatedMisinformationQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "Misinformation should be debated";
    protected string $questionId = "DebatedMisinformationQuestion";
}
