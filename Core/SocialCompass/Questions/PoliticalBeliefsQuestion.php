<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * about their political alignment.
 * Note: the topic of this question may have different meanings in different areas of the world
 */
class PoliticalBeliefsQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Regulation";
    protected string $maximumStepLabel = "Free Markets";
    protected string $questionText = "My political philosophy leans toward...";
    protected string $questionId = "PoliticalBeliefsQuestion";
}
