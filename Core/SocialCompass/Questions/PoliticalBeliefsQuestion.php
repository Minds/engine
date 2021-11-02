<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * about their political alignment.
 * Note: the topic of this question may have different meanings in different areas of the world
 */
class PoliticalBeliefsQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Left";
    protected string $maximumStepLabel = "Right";
    protected string $questionText = "Political Beliefs";
    protected string $questionId = "PoliticalBeliefsQuestion";
}
