<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validator for count requests of inbox and outbox.
 */
class SupermindCountRequestsValidator implements ValidatorInterface
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
  
        $status = $dataToValidate['status'] ?? null ;
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
