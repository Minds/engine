<?php

namespace Minds\Core\SocialCompass\Questions;

class OpinionsDisagreementQuestion extends BaseQuestion
{
    protected string $minimumStepLabel = "Less";
    protected string $maximumStepLabel = "More";
    protected string $questionText = "Opinions I disagree with";
    protected string $questionId = "OpinionsDisagreementQuestion";
}
