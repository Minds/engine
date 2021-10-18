<?php

namespace Minds\Core\SocialCompass\Questions;

class PoliticalBeliefsQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Left";
    protected string $maximumStepLabel = "Right";
    protected string $questionText = "Political Beliefs";

    protected const QuestionId = self::class;

    public function __construct()
    {
    }
}
