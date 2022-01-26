<?php

namespace Minds\Core\Recommendations\Validators;

use Minds\Core\Recommendations\Config\RecommendationsLocationsMappingConfig;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 * Validator class for GetRecommendationsRequest
 */
class GetRecommendationsRequestValidator implements ValidatorInterface
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
     * Checks if the 'location' parameter has been provided in the request
     * @param array $dataToValidate
     * @return bool
     */
    private function isLocationProvided(array $dataToValidate): bool
    {
        if (!isset($dataToValidate['location']) || empty($dataToValidate['location'])) {
            $this->errors->add(
                new ValidationError(
                    "location",
                    "The property 'location' must be provided."
                )
            );
        }

        if (!array_key_exists($dataToValidate['location'], RecommendationsLocationsMappingConfig::MAPPING)) {
            $this->errors->add(
                new ValidationError(
                    "location",
                    "The value provided for the 'location' property is incorrect. Please try again with a different value."
                )
            );
        }

        return true;
    }

    /**
     * Performs the validation of the Http request data
     * @param array $dataToValidate
     * @return bool
     */
    public function validate(array $dataToValidate): bool
    {
        $this->clearErrors();

        $this->isLocationProvided($dataToValidate);

        return $this->errors->count() === 0;
    }

    /**
     * Returns the list of validation errors if any
     * @return ValidationErrorCollection|null
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
