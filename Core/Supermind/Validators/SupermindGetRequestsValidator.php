<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

class SupermindGetRequestsValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors = null;

    private array $invalidStatuses = [
        SupermindRequestStatus::PENDING
    ];

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function validate(array|ServerRequestInterface $dataToValidate): bool
    {
        $this->resetErrors();

        if (!isset($dataToValidate['limit']) || $dataToValidate['limit'] < 1) {
            $this->errors->add(
                new ValidationError(
                    "limit",
                    "The 'limit' parameter must be provided and have a minimum value of '1'"
                )
            );
        }

        if (!isset($dataToValidate['offset']) || $dataToValidate['offset'] < 0) {
            $this->errors->add(
                new ValidationError(
                    "offset",
                    "The 'offset' parameter must be provided and have a minimum value of '0' (zero)"
                )
            );
        }
  
        $status = $dataToValidate['status'] ?? null;
        if (!is_null($status) && ($status < 1 || in_array(SupermindRequestStatus::from((int) $status), $this->invalidStatuses, true))) {
            $this->errors->add(
                new ValidationError(
                    "status",
                    "The provided 'status' parameter is invalid"
                )
            );
        }

        return $this->errors->count() === 0;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
