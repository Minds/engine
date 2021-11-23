<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they would like to interact with more or less
 * content of political nature
 */
class PhonesAddictedPeopleQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "I think people are too addicted to their phones";
    protected string $questionId = "PhonesAddictedPeopleQuestion";
}
