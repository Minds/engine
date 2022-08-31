<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestReplyType;
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

        if (isset($dataToValidate['mature']) && $dataToValidate['mature'] === true) {
            $this->errors->add(
                new ValidationError(
                    "mature",
                    "A Supermind request cannot be marked as NSFW"
                )
            );
        }

        if (isset($dataToValidate['paywall'])) {
            $this->errors->add(
                new ValidationError(
                    "paywall",
                    "A Supermind request cannot be monetized"
                )
            );
        }

        // TODO: Add validation for schedule option
        // TODO: Add validation for ellipsis menu options

        if (empty($dataToValidate['supermind_request'])) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request",
                    "The details for the Supermind request are missing"
                )
            );
        } else {
            $supermindRequest = $dataToValidate['supermind_request'];

            if (!isset($supermindRequest['receiver_guid']) || !$supermindRequest['receiver_guid']) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:receiver_guid",
                        "You must provide the Supermind request target"
                    )
                );
            }

            if (!isset($supermindRequest['reply_type']) || !$supermindRequest['reply_type']) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:reply_type",
                        "You must provide the Supermind request reply type"
                    )
                );
            } elseif (!in_array($supermindRequest['reply_type'], SupermindRequestReplyType::VALID_REPLY_TYPES)) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:reply_type",
                        "You must provide a valid Supermind request reply type"
                    )
                );
            }

            if (!isset($supermindRequest['payment_options'])) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options",
                        "Payment details must be provided"
                    )
                );
            } else {
                $paymentOptions = $supermindRequest['payment_options'];
                if (!isset($paymentOptions['amount']) || !$paymentOptions['amount']) {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_request:payment_options:amount",
                            "An amount of at least 0.01 must be provided"
                        )
                    );
                }

                if (!isset($paymentOptions['payment_type'])) {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_request:payment_options:payment_type",
                            "A valid payment type must be provided"
                        )
                    );
                }

                if (
                    $paymentOptions['payment_type'] === SupermindRequestPaymentMethod::CASH &&
                    (!isset($paymentOptions['payment_method_id']) || !$paymentOptions['payment_method_id'])
                ) {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_request:payment_options:payment_method_id",
                            "You must select a card to be used for the payment"
                        )
                    );
                }

                // TODO: validate payment method id belongs to logged-in user
            }
        }

        return $this->errors->count() === 0;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
