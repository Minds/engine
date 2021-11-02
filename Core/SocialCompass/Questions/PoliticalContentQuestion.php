<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they would like to interact with more or less
 * content of political nature
 */
class PoliticalContentQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Less";
    protected string $maximumStepLabel = "More";
    protected string $questionText = "Political Content";
    protected string $questionId = "PoliticalContentQuestion";
}
