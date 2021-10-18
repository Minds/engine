<?php

namespace Minds\Core\SocialCompass\Questions;

class EstablishmentQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Trustful";
    protected string $maximumStepLabel = "Critical";
    protected string $questionText = "Establishment";
    protected const QuestionId = self::class;

    public function __construct()
    {
    }
}
