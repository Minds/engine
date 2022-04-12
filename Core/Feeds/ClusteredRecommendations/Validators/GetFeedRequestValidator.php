<?php

namespace Minds\Core\Feeds\ClusteredRecommendations\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 * Validator class
 */
class GetFeedRequestValidator implements ValidatorInterface
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
        if (!empty($dataToValidate['limit'])) {
            if (!is_numeric($dataToValidate['limit'])) {
                $this->errors?->add(
                    new ValidationError(
                        "limit",
                        "The 'limit' parameter must be a number"
                    )
                );
            } elseif ($dataToValidate['limit'] < 0) {
                $this->errors?->add(
                    new ValidationError(
                        "limit",
                        "The 'limit' parameter must be 0 (zero) or greater"
                    )
                );
            } elseif ($dataToValidate['limit'] > 150) {
                $this->errors?->add(
                    new ValidationError(
                        "limit",
                        "The 'limit' parameter must be 150 or less"
                    )
                );
            }
        }

        return $this->errors->count() == 0;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
