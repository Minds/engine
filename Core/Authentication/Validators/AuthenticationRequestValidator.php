<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Validators;

use Minds\Entities\ValidationErrorCollection;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationRequestValidator implements \Minds\Interfaces\ValidatorInterface
{
    private ?ValidationErrorCollection $errors = null;

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function validate(array|ServerRequestInterface $dataToValidate): bool
    {
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
