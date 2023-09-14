<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as PaymentMethodsManager;
use Minds\Core\Session;
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Entities\User;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Minds\Interfaces\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Responsible for validating Supermind requests coming in
 */
class SupermindRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    public function __construct(
        private ?PaymentMethodsManager $paymentMethodsManager = null,
        private ?SupermindPaymentProcessor $paymentProcessor = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->paymentMethodsManager ??= Di::_()->get('Stripe\PaymentMethods\Manager');
        $this->paymentProcessor ??= new SupermindPaymentProcessor();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @param array|ServerRequestInterface $dataToValidate
     * @return bool
     * @throws Exception
     */
    public function validate(array|ServerRequestInterface $dataToValidate): bool
    {
        $this->resetErrors();

        $this->checkActivitySettings($dataToValidate);

        if (empty($dataToValidate['supermind_request'])) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request",
                    "The details for the Supermind request are missing"
                )
            );
        } else {
            $supermindRequest = $dataToValidate['supermind_request'];

            $this->checkSupermindDetails($supermindRequest);
        }

        return $this->errors->count() === 0;
    }

    private function checkActivitySettings(array|ServerRequestInterface $dataToValidate): void
    {
        if (isset($dataToValidate['mature']) && $dataToValidate['mature'] === true) {
            $this->errors->add(
                new ValidationError(
                    "mature",
                    "A Supermind request cannot be marked as NSFW"
                )
            );
        }

        if (!empty($dataToValidate['time_created'])) {
            $this->errors->add(
                new ValidationError(
                    "time_created",
                    "A Supermind request cannot be a scheduled post"
                )
            );
        }

        if (isset($dataToValidate['post_to_permaweb']) && $dataToValidate['post_to_permaweb']) {
            $this->errors->add(
                new ValidationError(
                    "post_to_permaweb",
                    "A Supermind request cannot be a Permaweb post"
                )
            );
        }

        if (isset($dataToValidate['access_id']) && $dataToValidate['access_id'] != ACCESS_PUBLIC) {
            $this->errors->add(
                new ValidationError(
                    'access_id',
                    "A Supermind request must be a public post"
                )
            );
        }

        if (isset($dataToValidate['license']) && $dataToValidate['license'] !== "all-rights-reserved") {
            $this->errors->add(
                new ValidationError(
                    "license",
                    "A Supermind request must have an 'All Rights Reserved' license applied"
                )
            );
        }

        if (isset($dataToValidate['wire_threshold']) || isset($dataToValidate['paywall'])) {
            $this->errors->add(
                new ValidationError(
                    isset($dataToValidate['wire_threshold']) ? 'wire_threshold' : 'paywall',
                    'A Supermind request cannot be monetized'
                )
            );
        }
    }

    /**
     * @param array $supermindRequest
     * @return void
     * @throws Exception
     */
    private function checkSupermindDetails(array $supermindRequest): void
    {
        if (!isset($supermindRequest['receiver_guid']) || !$supermindRequest['receiver_guid']) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:receiver_guid",
                    "You must provide the Supermind request target"
                )
            );
            return;
        }
        $receiver = $this->buildUser($supermindRequest['receiver_guid']);
        if ($this->getLoggedInUserGuid() === $receiver->getGuid()) {
            throw new UserErrorException(
                "It is not possible to send a Supermind offer to yourself"
            );
        }

        if (!isset($supermindRequest['terms_agreed']) || !$supermindRequest['terms_agreed']) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:terms_agreed",
                    "You must agree to the terms and condition in order to create a Supermind request"
                )
            );
        }

        if (!isset($supermindRequest['reply_type'])) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:reply_type",
                    "You must provide the Supermind request reply type"
                )
            );
        } elseif (
            !in_array(
                $supermindRequest['reply_type'],
                SupermindRequestReplyType::VALID_REPLY_TYPES,
                true
            )
        ) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:reply_type",
                    "You must provide a valid Supermind request reply type"
                )
            );
        }

        if ($supermindRequest['reply_type'] === SupermindRequestReplyType::LIVE && $supermindRequest['twitter_required']) {
            $this->errors->add(
                new ValidationError(
                    "supermind_request:twitter_required",
                    "You cannot require Twitter for a live Supermind request"
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

            if (!isset($paymentOptions['payment_type'])) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options:payment_type",
                        "A valid payment type must be provided"
                    )
                );
            } elseif (!in_array($paymentOptions['payment_type'], SupermindRequestPaymentMethod::VALID_PAYMENT_METHODS, true)) {
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
            } elseif ($paymentOptions['payment_type'] === SupermindRequestPaymentMethod::CASH) {
                $isPaymentMethodIdValid = $this->paymentMethodsManager->checkPaymentMethodOwnership(
                    $this->getLoggedInUserGuid(),
                    $paymentOptions['payment_method_id']
                );
                if (!$isPaymentMethodIdValid) {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_request:payment_options:payment_method_id",
                            "The provided payment method is not associated with your account"
                        )
                    );
                }
            }

            $this->loadSupermindPaymentSettings($supermindRequest['receiver_guid']);

            if (!isset($paymentOptions['amount']) || !$paymentOptions['amount']) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options:amount",
                        "An amount of at least " . $this->paymentProcessor->getMinimumAllowedAmount($paymentOptions['payment_type']) . " must be provided"
                    )
                );
            } elseif (!$this->paymentProcessor->isPaymentAmountAllowed($paymentOptions['amount'], $paymentOptions['payment_type'])) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options:amount",
                        "An amount of at least " . $this->paymentProcessor->getMinimumAllowedAmount($paymentOptions['payment_type']) . " must be provided"
                    )
                );
            }
        }
    }

    private function getLoggedInUserGuid(): string
    {
        return (string) Session::getLoggedinUser()->getGuid();
    }

    /**
     * @param string $userGuid
     * @return User
     * @throws ServerErrorException
     */
    private function buildUser(string $userGuid): User
    {
        $user = $this->entitiesBuilder->getByUserByIndex($userGuid);
        if ($user === null) {
            // try build from guid
            $user = $this->entitiesBuilder->single($userGuid);
        }
        if (!($user instanceof User)) {
            throw new ServerErrorException('Could not build a user from the provided target GUID');
        }
        return $user;
    }

    /**
     * @param string $targetUserGuid
     * @return void
     * @throws ServerErrorException
     */
    private function loadSupermindPaymentSettings(string $targetUserGuid): void
    {
        // try build from username
        $receiver = $this->buildUser($targetUserGuid);
        $this->paymentProcessor->setUser($receiver);
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
