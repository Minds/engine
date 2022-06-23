<?php

namespace Minds\Core\Nostr\RequestValidators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 *
 */
class GetEventsRequestValidator implements ValidatorInterface
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

        if (!$dataToValidate['authors']) {
            $this->errors->add(
                new ValidationError(
                    "authors",
                    "A list of authors must be provided for the 'authors' parameter."
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
