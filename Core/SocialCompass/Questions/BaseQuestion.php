<?php

namespace Minds\Core\SocialCompass\Questions;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * Defines what a generic Social Compass question is
 *
 * @method string getMinimumStepLabel()
 * @method self setMinimumStepLabel(string $minimumStepLabel)
 *
 * @method string getMaximumStepLabel()
 * @method self setMaximumStepLabel(string $maximumStepLabel)
 *
 * @method string getQuestionText()
 * @method self setQuestionText(string $questionText)
 *
 * @method int getStepSize()
 * @method self setStepSize(int $stepSize)
 *
 * @method int getCurrentValue()
 * @method self setCurrentValue(int $currentValue)
 *
 * @method string getQuestionId()
 * @method self setQuestionId(string $questionId)
 *
 * @method int getMaximumRangeValue()
 * @method self setMaximumRangeValue(int $maximumRangeValue)
 *
 * @method int getMinimumRangeValue()
 * @method self setMinimumRangeValue(int $minimumRangeValue)
 *
 * @method int getDefaultValue()
 * @method self setDefaultValue(int $defaultValue)
 */
class BaseQuestion implements ExportableInterface
{
    use MagicAttributes;
    /**
     * @var string The label to show to the use in relation to the minimum value of the range for the answer to be given
     */
    protected string $minimumStepLabel = "";

    /**
     * @var string The label to show to the use in relation to the maximum value of the range for the answer to be given
     */
    protected string $maximumStepLabel = "";

    /**
     * @var string
     */
    protected string $questionText = "";

    /**
     * @var int The increment size to be used in the FE to determine the steps for the input range
     */
    protected int $stepSize = 10;

    /**
     * @var int
     */
    protected int $currentValue = 50;

    /**
     * @var string
     */
    protected string $questionId = "";

    /**
     * Maximum value for the question's range of values
     */
    protected int $maximumRangeValue = 100;

    /**
     * Minimum value for the question's range of values
     */
    protected int $minimumRangeValue = 0;

    /**
     * The initial value for the question if no answer has been provided previously
     */
    protected int $defaultValue = 50;

    /**
     * Defines the properties that are user-friendly to return to the FE
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            "minimumStepLabel" => $this->getMinimumStepLabel(),
            "maximumStepLabel" => $this->getMaximumStepLabel(),
            "questionText" => $this->getQuestionText(),
            "questionId" => $this->getQuestionId(),
            "stepSize" => $this->getStepSize(),
            "defaultValue" => $this->getDefaultValue(),
            "currentValue" => $this->getCurrentValue(),
            "maximumRangeValue" => $this->getMaximumRangeValue(),
            "minimumRangeValue" => $this->getMinimumRangeValue(),
        ];
    }
}
