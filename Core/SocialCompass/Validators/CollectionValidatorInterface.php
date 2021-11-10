<?php

namespace Minds\Core\SocialCompass\Validators;

use Minds\Entities\ValidationErrorCollection;

/**
 * The interface defining the methods to be implemented by a validator of Social Compass answers
 */
interface CollectionValidatorInterface
{
    /**
     * Validates the array of answers being provided and returns a collection of validation errors if any
     * @return bool
     */
    public function validate(): bool;

    /**
     * Returns the collection of validation errors, null otherwise
     * @return ?ValidationErrorCollection
     */
    public function errors(): ?ValidationErrorCollection;
}
