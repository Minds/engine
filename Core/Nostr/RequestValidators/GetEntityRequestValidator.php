<?php

namespace Minds\Core\Nostr\RequestValidators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;

class GetEntityRequestValidator implements \Minds\Interfaces\ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

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

        if (!$dataToValidate['hash']) {
            $this->errors->add(
                new ValidationError(
                    "hash",
                    "A value for the 'hash' parameter must be provided."
                )
            );
        }
        return !$this->errors->count();
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
