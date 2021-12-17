<?php

namespace Minds\Core\Feeds\UnseenTopFeed\Validators;

use Minds\Entities\ValidationErrorCollection;

/**
 * The interface defining the methods to be implemented by a validator of Social Compass answers
 */
interface ValidatorInterface
{
    /**
     * Validates the array of answers being provided and returns a collection of validation errors if any
     * @param $dataToValidate
     * @return bool
     */
    public function validate($dataToValidate): bool;

    /**
     * Returns the collection of validation errors, null otherwise
     * @return ?ValidationErrorCollection
     */
    public function getErrors(): ?ValidationErrorCollection;
}
