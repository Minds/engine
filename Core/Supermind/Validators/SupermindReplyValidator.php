<?php

namespace Minds\Core\Supermind\Validators;

use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

class SupermindReplyValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    public function validate(array $dataToValidate): bool
    {
        $this->resetErrors();

        return $this->errors->count() === 0;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
