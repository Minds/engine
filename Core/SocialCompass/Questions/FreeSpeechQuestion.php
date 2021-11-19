<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they are either less or more prone to allow free speech
 */
class FreeSpeechQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Censored";
    protected string $maximumStepLabel = "Debated";
    protected string $questionText = "I think misinformation should be...";
    protected string $questionId = "FreeSpeechQuestion";
}
