<?php

namespace Minds\Entities;

/**
 *
 */
interface ValidatableObjectInterface
{
    /**
     * Performs the validation of the Model data and returns a validation error collection
     * @return ValidationErrorCollection
     */
    public function validate(): ValidationErrorCollection;
}
