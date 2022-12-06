<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Validators;

use Exception;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as PaymentMethodsManager;
use Minds\Core\Session;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

class BoostCreateRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors = null;

    public function __construct(
        private ?PaymentMethodsManager $paymentMethodsManager = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->paymentMethodsManager ??= Di::_()->get('Stripe\PaymentMethods\Manager');
        $this->mindsConfig ??= Di::_()->get('Config');
    }

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @param array $dataToValidate
     * @return bool
     * @throws Exception
     */
    public function validate(array $dataToValidate): bool
    {
        $this->resetErrors();

        if (!isset($dataToValidate['entity_guid'])) {
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    'Entity GUID must be provided'
                )
            );
        } elseif (!is_numeric($dataToValidate['entity_guid'])) {
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    'Entity GUID must be a valid guid'
                )
            );
        }

        if (!isset($dataToValidate['target_suitability'])) {
            $this->errors->add(
                new ValidationError(
                    'target_suitability',
                    'Target suitability must be provided'
                )
            );
        } elseif (!in_array($dataToValidate['target_suitability'], BoostTargetSuitability::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'target_suitability',
                    'Target suitability must be one of the valid options'
                )
            );
        }

        if (!isset($dataToValidate['target_location'])) {
            $this->errors->add(
                new ValidationError(
                    'target_location',
                    'Target location must be provided'
                )
            );
        } elseif (!in_array($dataToValidate['target_location'], BoostTargetLocation::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'target_location',
                    'Target location must be one of the valid options'
                )
            );
        }

        if (!isset($dataToValidate['payment_method'])) {
            $this->errors->add(
                new ValidationError(
                    'payment_method',
                    'Payment method must be provided'
                )
            );
        } elseif (!in_array($dataToValidate['payment_method'], BoostPaymentMethod::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'payment_method',
                    'Payment method must be one of the valid options'
                )
            );
        } elseif ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::CASH) {
            $isPaymentMethodIdValid = $this->paymentMethodsManager->checkPaymentMethodOwnership(
                $this->getLoggedInUserGuid(),
                $dataToValidate['payment_method_id']
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

        $this->checkDailyBid($dataToValidate);
        $this->checkDurationDays($dataToValidate);

        return $this->errors->count() === 0;
    }

    private function checkDailyBid(array $dataToValidate): void
    {
        if (!isset($dataToValidate['daily_bid'])) {
            $this->errors->add(
                new ValidationError(
                    'daily_bid',
                    'Daily bid must be provided'
                )
            );
        } elseif (!is_numeric($dataToValidate['daily_bid'])) {
            $this->errors->add(
                new ValidationError(
                    'daily_bid',
                    'Daily bid must be a numeric value'
                )
            );
        } elseif (isset($dataToValidate['payment_method'])) {
            if ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::CASH) {
                if ((float) $dataToValidate['daily_bid'] < $this->mindsConfig->get('boost')['min']['cash']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be lower than \${$this->mindsConfig->get('boost')['min']['cash']}"
                        )
                    );
                } elseif ((float) $dataToValidate['daily_bid'] > $this->mindsConfig->get('boost')['max']['cash']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be higher than \${$this->mindsConfig->get('boost')['max']['cash']}"
                        )
                    );
                }
            } elseif ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::OFFCHAIN_TOKENS) {
                if ((float) $dataToValidate['daily_bid'] < $this->mindsConfig->get('boost')['min']['offchain_tokens']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be lower than {$this->mindsConfig->get('boost')['min']['offchain_tokens']} MINDS tokens"
                        )
                    );
                } elseif ((float) $dataToValidate['daily_bid'] > $this->mindsConfig->get('boost')['max']['offchain_tokens']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be higher than {$this->mindsConfig->get('boost')['max']['offchain_tokens']} MINDS tokens"
                        )
                    );
                }
            }
        }
    }

    private function checkDurationDays(array $dataToValidate): void
    {
        if (!isset($dataToValidate['duration_days'])) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    'Daily bid must be provided'
                )
            );
        } elseif (!is_numeric($dataToValidate['duration_days'])) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    'Daily bid must be a numeric value'
                )
            );
        } elseif ($dataToValidate['duration_days'] < $this->mindsConfig->get('boost')['duration']['min']) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    "Duration in days cannot be less than {$this->mindsConfig->get('boost')['duration']['cash']['min']} days"
                )
            );
        } elseif ($dataToValidate['duration_days'] > $this->mindsConfig->get('boost')['duration']['max']) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    "Duration in days cannot be more than {$this->mindsConfig->get('boost')['duration']['cash']['max']} days"
                )
            );
        }
    }

    private function getLoggedInUserGuid(): string
    {
        return (string) Session::getLoggedinUser()->getGuid();
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
