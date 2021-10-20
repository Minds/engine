<?php

namespace Minds\Core\SocialCompass\Questions;

class OpinionsDisagreementQuestion extends BaseQuestion
{
    public string $minimumStepLabel = "Less";
    public string $maximumStepLabel = "More";
    public string $questionText = "Opinions I disagree with";

    public string $questionId = "OpinionsDisagreementQuestion";
}
