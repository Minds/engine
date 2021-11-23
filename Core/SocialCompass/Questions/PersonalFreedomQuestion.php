<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they are either less or more prone to allow free speech
 */
class PersonalFreedomQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "More personal freedom is better";
    protected string $questionId = "PersonalFreedomQuestion";
}
