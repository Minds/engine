<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they would like to interact with more or less
 * content of political nature
 */
class PoliticalContentQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Apolitical";
    protected string $maximumStepLabel = "Political";
    protected string $questionText = "I prefer content that is...";
    protected string $questionId = "PoliticalContentQuestion";
}
