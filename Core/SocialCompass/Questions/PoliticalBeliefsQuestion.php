<?php

namespace Minds\Core\SocialCompass\Questions;

class PoliticalBeliefsQuestion extends BaseQuestion
{
    public string $minimumStepLabel = "Left";
    public string $maximumStepLabel = "Right";
    public string $questionText = "Political Beliefs";

    public string $questionId = "PoliticalBeliefsQuestion";
}
