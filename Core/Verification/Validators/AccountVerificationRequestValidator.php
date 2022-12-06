<?php

namespace Minds\Core\Verification\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Psr\Http\Message\ServerRequestInterface;

class AccountVerificationRequestValidator implements \Minds\Interfaces\ValidatorInterface
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
        $this->resetErrors();

        if (!isset($dataToValidate->getUploadedFiles()['image'])) {
            $this->errors->add(
                new ValidationError(
                    "image",
                    "The file with name 'image' must be provided"
                )
            );
        }

        if (!array_key_exists('device_type', $dataToValidate->getParsedBody())) {
            $this->errors->add(
                new ValidationError(
                    "device_type",
                    "The device type must be provided"
                )
            );
        }

        if (!array_key_exists('geo', $dataToValidate->getParsedBody())) {
            $this->errors->add(
                new ValidationError(
                    "geo",
                    "The device type must be provided"
                )
            );
        }

        if (!array_key_exists('sensor_data', $dataToValidate->getParsedBody())) {
            $this->errors->add(
                new ValidationError(
                    "sensor_data",
                    "The sensor data must be provided"
                )
            );
        } elseif (@json_decode($dataToValidate->getParsedBody()['sensor_data']) && json_last_error() !== JSON_ERROR_NONE) {
            $this->errors->add(
                new ValidationError(
                    "sensor_data",
                    "The sensor data must be a valid JSON string"
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
