<?php

namespace Minds\Core\SocialCompass\Questions;

/**
 * Question for the Social Compass asking the user
 * if they trust or criticise the establishment
 */
class EstablishmentQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Trustful";
    protected string $maximumStepLabel = "Critical";
    protected string $questionText = "Establishment";
    protected string $questionId = "EstablishmentQuestion";
}
