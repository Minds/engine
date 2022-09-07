<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

class SupermindGetRequestsValidator implements ValidatorInterface
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

        if (!isset($dataToValidate['limit']) || $dataToValidate['limit'] < 1) {
            $this->errors->add(
                new ValidationError(
                    "limit",
                    "The 'limit' parameter must be provided and have a minimum value of '1'"
                )
            );
        }

        if (!isset($dataToValidate['offset']) || $dataToValidate['offset'] < 0) {
            $this->errors->add(
                new ValidationError(
                    "limit",
                    "The 'offset' parameter must be provided and have a minimum value of '0' (zero)"
                )
            );
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
