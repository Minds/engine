<?php

namespace Minds\Core\SocialCompass\Questions;

class PoliticalContentQuestion extends BaseQuestion
{
    public string $minimumStepLabel = "Less";
    public string $maximumStepLabel = "More";
    public string $questionText = "Political Content";

    public string $questionId = "PoliticalContentQuestion";
}
