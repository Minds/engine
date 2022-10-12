<?php

namespace Minds\Core\Feeds\Supermind\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 * Responsible for validating the Supermind feed request object
 */
class SupermindFeedRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors = null;

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function validate(array $dataToValidate): bool
    {
        $this->resetErrors();

        if (empty($dataToValidate['limit'])) {
            $this->errors->add(new ValidationError(
                'limit',
                "The property 'limit' must be provided."
            ));
        } elseif (!is_numeric($dataToValidate['limit'])) {
            $this->errors->add(new ValidationError(
                'limit',
                "The property 'limit' must be a numeric value."
            ));
        } elseif ($dataToValidate['limit'] < 0 || $dataToValidate['limit'] > 500) {
            $this->errors->add(new ValidationError(
                'limit',
                "The property 'limit' must have a value between 0 and 500"
            ));
        }

        return $this->errors->count() === 0;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
