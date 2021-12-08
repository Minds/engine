<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they are either less or more prone to allow free speech
 */
class RequiredGovernmentRegulationsQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Disagree";
    protected string $maximumStepLabel = "Agree";
    protected string $questionText = "Government regulation is sometimes required";
    protected string $questionId = "RequiredGovernmentRegulationsQuestion";
}
