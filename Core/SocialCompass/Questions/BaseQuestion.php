<?php

namespace Minds\Core\SocialCompass\Questions;

class BaseQuestion
{
    protected string $minimumStepLabel = "";
    protected string $maximumStepLabel = "";
    protected string $questionText = "";
    protected int $totalSteps = 10;
    protected int $currentValue = 50;

    /**
     * Maximum value for the question's range of values
     */
    protected const MaximumRangeValue = 100;

    /**
     * Minimum value for the question's range of values
     */
    protected const MinimumRangeValue = 0;

    /**
     * The initial value for the question if no answer has been provided previously
     */
    protected const DefaultValue = 50;

    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }
}
