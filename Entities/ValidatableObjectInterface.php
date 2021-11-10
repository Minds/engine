<?php

namespace Minds\Entities;

/**
 *
 */
interface ValidatableObjectInterface
{
    /**
     * Performs the validation of the Model data and returns true if passed, false otherwise
     * @return bool
     */
    public function validate(): bool;

    /**
     * Returns the validation error or null if no error occurred during validation
     * @return ?ValidationError
     */
    public function error(): ?ValidationError;
}
