<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they feel like they part of either a narrow or wide echo chamber
 */
class EchoChamberQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Less";
    protected string $maximumStepLabel = "More";
    protected string $questionText = "Echo Chamber";
    protected string $questionId = "EchoChamberQuestion";
}
