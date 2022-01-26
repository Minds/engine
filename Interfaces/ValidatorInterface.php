<?php

namespace Minds\Interfaces;

use Minds\Entities\ValidationErrorCollection;

interface ValidatorInterface
{
    /**
     * Validates the array of answers being provided and returns true if any errors have been found, false otherwise
     * @param array $dataToValidate
     * @return bool
     */
    public function validate(array $dataToValidate): bool;

    /**
     * Returns a collection of validation errors
     * @return ValidationErrorCollection|null
     */
    public function getErrors(): ?ValidationErrorCollection;
}
