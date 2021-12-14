<?php

namespace Minds\Core\Feeds\UnseenTopFeed\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;

/**
 * Validates the request data for the /api/v3/newsfeed/feed/unseen-top
 */
class UnseenTopFeedRequestValidator implements ValidatorInterface
{
    public function __construct(
        private ?ValidationErrorCollection $errors = null
    ) {
        $this->errors = $this->errors ?? new ValidationErrorCollection();
    }

    /**
     * Reset the errors object to an empty collection
     */
    private function clearErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * Validates the array of answers being provided and returns a collection of validation errors if any
     * @param array $dataToValidate
     * @return bool
     */
    public function validate($dataToValidate): bool
    {
        $this->clearErrors();

        $this->isLimitProvided($dataToValidate);

        return $this->errors->count() === 0;
    }

    /**
     * Performs validation on the property 'limit'
     * @param array $dataToValidate
     * @return bool
     */
    private function isLimitProvided(array $dataToValidate): bool
    {
        if (!isset($dataToValidate['limit']) || empty($dataToValidate['limit'])) {
            $this->errors->add(new ValidationError(
                'limit',
                "The property 'limit' must be provided."
            ));
            return false;
        }

        if (!is_numeric($dataToValidate['limit'])) {
            $this->errors->add(new ValidationError(
                'limit',
                "The property 'limit' must be a numeric value."
            ));
            return false;
        }

        if ($dataToValidate['limit'] < 0 || $dataToValidate['limit'] > 500) {
            $this->errors->add(new ValidationError(
                'limit',
                "The property 'limit' must have a value between 0 and 500"
            ));
            return false;
        }

        return true;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
