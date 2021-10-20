<?php

namespace Minds\Core\SocialCompass\Questions;

class BaseQuestion
{
    public string $minimumStepLabel = "";
    public string $maximumStepLabel = "";
    public string $questionText = "";
    public int $totalSteps = 10;
    public int $currentValue = 50;

    /**
     * Maximum value for the question's range of values
     */
    public int $maximumRangeValue = 100;

    /**
     * Minimum value for the question's range of values
     */
    public int $minimumRangeValue = 0;

    /**
     * The initial value for the question if no answer has been provided previously
     */
    public int $defaultValue = 50;
}
