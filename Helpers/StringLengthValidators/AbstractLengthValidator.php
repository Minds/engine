<?php

namespace Minds\Helpers\StringLengthValidators;

use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\StringLengthException;

/**
 * Abstract length validator. Can be extended to create string length
 * validators for specific fields.
 *
 * Implementation example:
 * class CustomLengthValidator extends AbstractLengthValidator
 * {
 *    public function __construct()
 *    {
 *        parent::__construct(
 *            fieldName: 'customfield',
 *            min: 5,
 *            max: 10
 *        );
 *    }
 * }
 */
abstract class AbstractLengthValidator
{
    // name of the field.
    protected $fieldName = null;

    // minimum length of the field.
    protected $min = null;

    // maximum length of the field.
    protected $max = null;

    /**
     * Constructor
     * @param string $fieldName - name of the field.
     * @param integer $min - minimum length of the field.
     * @param integer $max - maximum length of the field.
     */
    public function __construct(string $fieldName, int $min, int $max)
    {
        if (strlen($fieldName) < 1) {
            throw new ServerErrorException('Cannot construct validator');
        }
        $this->fieldName = $fieldName;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Validates a string is above or equal to the min and below or equal to the max bounds for length.
     * @param string $target - target string to check.
     * @param string $target - overrides name in thrown error message - by default fieldName will be used.
     * @throws StringLengthException - if string length is determined to be invalid.
     * @return boolean - true if string is within defined bounds.
     */
    public function validate(?string $target = '', $nameOverride = ''): bool
    {
        $stringLength = mb_strlen($target);
        if (!($stringLength <= $this->getMax() && $stringLength >= $this->getMin())) {
            throw new StringLengthException(self::limitsToString($nameOverride));
        }
        return true;
    }

    /**
     * Validates a string's max length ONLY and trims it appropriately if the max bound is exceeded,
     * adding in an ellipsis after truncating at the maximum length.
     * @param string $target - target string to check / truncate.
     * @return string truncated string if required, or the original string if within the defined bounds.
     */
    public function validateMaxAndTrim(?string $target = ''): string
    {
        return mb_strlen($target) > $this->getMax() ?
            mb_substr($target, 0, $this->getMax()).'...' :
            $target ?? '';
    }

    /**
     * Get max bound.
     * @return int - max bound.
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * Get min bound.
     * @return int - min bound.
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Gets field name.
     * @return string - field name.
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Returns a string containing the limits for a given key, for consumption in user facing
     * error messages, for example: "Invalid username. Must be between 1 and 50 characters.".
     * @param string $nameOverride - overrides the field name in the returned string.
     * @return string - contains limits as a string for consumption in error messages.
     */
    public function limitsToString(string $nameOverride = null): string
    {
        $name = $nameOverride ? $nameOverride : $this->getFieldName();
        return "Invalid " . $name .". Must be between ". $this->getMin() ." and ". $this->getMax() ." characters.";
    }
}
