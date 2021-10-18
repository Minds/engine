<?php

namespace Minds\Core\SocialCompass\Questions;

class PoliticalContentQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Less";
    protected string $maximumStepLabel = "More";
    protected string $questionText = "Political Content";
    protected const QuestionId = self::class;

    public function __construct()
    {
    }
}
