<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking users
 * if they would like to interact with more or less
 * opinions that challenge their own
 */
class ChallengingOpinionsQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "I prefer to see opinions that challenge my own";
    protected string $questionId = "ChallengingOpinionsQuestion";
}
