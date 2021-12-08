<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they would like to interact with more or less
 * content of political nature
 */
class AnyoneChangeTheirMindQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "Anyone can change their mind";
    protected string $questionId = "AnyoneChangeTheirMindQuestion";
}
