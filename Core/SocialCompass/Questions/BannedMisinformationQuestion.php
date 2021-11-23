<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they feel like they part of either a narrow or wide echo chamber
 */
class BannedMisinformationQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "Misinformation should be banned";
    protected string $questionId = "BannedMisinformationQuestion";
}
