<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they are either less or more prone to allow free speech
 */
class FreeSpeechQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Less";
    protected string $maximumStepLabel = "More";
    protected string $questionText = "Free Speech";
    protected string $questionId = "FreeSpeechQuestion";
}
