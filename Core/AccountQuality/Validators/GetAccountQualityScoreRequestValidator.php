<?php

namespace Minds\Core\AccountQuality\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

class GetAccountQualityScoreRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    private function reset(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    public function validate(array $dataToValidate): bool
    {
        $this->reset();

        if (empty($dataToValidate['targetUserGuid'])) {
            $this->errors->add(new ValidationError("targetUserId", "The user guid must be provided."));
        }

        return $this->errors->count() == 0;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
