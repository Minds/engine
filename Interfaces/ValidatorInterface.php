<?php

namespace Minds\Interfaces;

use Minds\Entities\ValidationErrorCollection;
use Psr\Http\Message\ServerRequestInterface;

interface ValidatorInterface
{
    /**
     * Validates the array of answers being provided and returns true if any errors have been found, false otherwise
     * @param array|ServerRequestInterface $dataToValidate
     * @return bool
     */
    public function validate(array|ServerRequestInterface $dataToValidate): bool;

    /**
     * Returns a collection of validation errors
     * @return ValidationErrorCollection|null
     */
    public function getErrors(): ?ValidationErrorCollection;
}
