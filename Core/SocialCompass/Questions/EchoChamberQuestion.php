<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they feel like they part of either a narrow or wide echo chamber
 */
class EchoChamberQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Open";
    protected string $maximumStepLabel = "Closed";
    protected string $questionText = "I prefer my echo chamber to be...";
    protected string $questionId = "EchoChamberQuestion";
}
