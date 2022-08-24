<?php

namespace Minds\Core\Supermind\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 * Responsible for validating Supermind requests coming in
 */
class SupermindRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    public function validate(array $dataToValidate): bool
    {
        $this->resetErrors();

        if (!isset($dataToValidate['receiver_guid']) || !$dataToValidate['receiver_guid']) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:receiver_guid",
                    "You must provide the Supermind request target."
                )
            );
        }

        if (!isset($dataToValidate['payment_options'])) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:payment_options",
                    "Payment details must be provided."
                )
            );
        } else {
            $paymentOptions = $dataToValidate['payment_options'];
            if (!isset($paymentOptions['amount']) || !$paymentOptions['amount']) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options:amount",
                        "An amount of at least 0.01 must be provided."
                    )
                );
            }

            if (!isset($paymentOptions['payment_method_id']) || !$paymentOptions['payment_method_id']) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options:payment_method_id",
                        "You must select a card to be used for the payment."
                    )
                );
            }
        }

        return $this->errors->count() === 0;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
