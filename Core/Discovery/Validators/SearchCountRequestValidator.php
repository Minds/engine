<?php

namespace Minds\Core\Discovery\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;

/**
 * Validates the request data for the api/v3/discovery/search/count
 */
class SearchCountRequestValidator
{
    public function __construct(
        private ?ValidationErrorCollection $errors = null
    ) {
        $this->errors = $this->errors ?? new ValidationErrorCollection();
    }

    /**
     * @param array $dataToValidate
     * @return bool
     */
    public function validate($dataToValidate): bool
    {
        $this->clearErrors();

        if (!isset($dataToValidate['from_timestamp']) || empty($dataToValidate['from_timestamp'])) {
            $this->errors->add(new ValidationError(
                'from_timestamp',
                "The property 'from_timestamp' must be provided."
            ));
        }

        if (isset($dataToValidate['from_timestamp']) && !$this->isValidTimestamp($dataToValidate['from_timestamp'])) {
            $this->errors->add(new ValidationError(
                'from_timestamp',
                "The property 'from_timestamp' must be a valid timestamp."
            ));
        }

        return $this->errors->count() === 0;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }

    /**
     * validates a timestamp
     * @param array $timestamp
     * @return bool
     */
    private function isValidTimestamp(int $timestamp)
    {
        return $timestamp > strtotime('-1 years') && $timestamp <= PHP_INT_MAX;
    }

    /**
     * Reset the errors object to an empty collection
     */
    private function clearErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }
}
