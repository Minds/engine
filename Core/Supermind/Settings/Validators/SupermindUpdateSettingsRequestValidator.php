<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings\Validators;

use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 * Request validator for update Supermind settings requests.
 */
class SupermindUpdateSettingsRequestValidator implements ValidatorInterface
{
    /** @var ValidationErrorCollection $errors - validation errors */
    private ?ValidationErrorCollection $errors = null;

    public function __construct(private ?Settings $defaultSettings = null)
    {
        $this->defaultSettings ??= new Settings();
    }

    /**
     * Set instance errors back to default state.
     * @return void
     */
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

        if (!count($dataToValidate)) {
            $this->errors->add(
                new ValidationError(
                    'body',
                    "Settings array must be provided"
                )
            );
        }

        foreach ($dataToValidate as $key => $value) {
            $this->checkDecimalPlaces($key, $value, 2)
                ->checkMinTokenAmount($key, $value)
                ->checkMinCashAmount($key, $value);
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

    /**
     * Check decimal places in a float are not more than a given amount.
     * @param string $fieldName - field name to check for.
     * @param float $value - value to check.
     * @param integer $decimalPlaces - maximum decimal places for value.
     * @return self
     */
    private function checkDecimalPlaces(string $fieldName, float $value, int $decimalPlaces): self
    {
        if ((int) strpos(strrev((string) $value), ".") > $decimalPlaces) {
            $this->errors->add(
                new ValidationError(
                    $fieldName,
                    "$fieldName amount can only be a maximum of $decimalPlaces decimal places"
                )
            );
        }
        return $this;
    }

    /**
     * Check minimum token amount - add any errors to instance member errors.
     * @param string $key - key to check for.
     * @param float $value - value to check for.
     * @return self
     */
    private function checkMinTokenAmount(string $key, float $value): self
    {
        $minimumTokenAmount = $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN);

        if ($key === 'min_offchain_tokens' && $value < $minimumTokenAmount) {
            $this->errors->add(
                new ValidationError(
                    'min_offchain_tokens',
                    "Minimum token amount must be more than $minimumTokenAmount token"
                )
            );
        }
        return $this;
    }

    /**
     * Check minimum cash amount - add any errors to instance member errors.
     * @param string $key - key to check for.
     * @param float $value - value to check for.
     * @return self
     */
    private function checkMinCashAmount(string $key, float $value): self
    {
        $minimumCashAmount = $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::CASH);

        if ($key === 'min_cash' && $value < $minimumCashAmount) {
            $this->errors->add(
                new ValidationError(
                    'min_cash',
                    "Minimum cash amount must be more than $$minimumCashAmount"
                )
            );
        }
        return $this;
    }
}
