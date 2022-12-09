<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings\Models;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Traits\MagicAttributes;

/**
 * Settings model
 * @method float getMinCash()
 * @method float getMinOffchainTokens()
 * @method Settings setMinCash(float $amount)
 * @method Settings setMinOffchainTokens(float $amount)
 */
class Settings implements \JsonSerializable
{
    use MagicAttributes;

    /**
     * @const float Defines the minimum allowed amount for a Supermind requests
     */
    private const SUPERMIND_REQUEST_MINIMUM_AMOUNT = [
        SupermindRequestPaymentMethod::CASH => 1.00,
        SupermindRequestPaymentMethod::OFFCHAIN_TOKEN => 1.00,
    ];

    public function __construct(
        private ?float $minOffchainTokens = null,
        private ?float $minCash = null,
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->minOffchainTokens ??= $this->getDefaultMinimumAmount(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN);
        $this->minCash ??= $this->getDefaultMinimumAmount(SupermindRequestPaymentMethod::CASH);
    }

    /**
     * Export object as array.
     * @return array - array containing object data.
     */
    public function export(): array
    {
        return [
            'min_offchain_tokens' => $this->minOffchainTokens,
            'min_cash' => $this->minCash,
        ];
    }

    /**
     * Called on JSON serialization.
     * @return array - array that will be JSON serialized.
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }

    /**
     * Get default minimum amount for given payment method.
     * @param integer $paymentMethod - payment method to get default minimum amount for.
     * @return float - default minimum amount.
     */
    public function getDefaultMinimumAmount(int $paymentMethod): float
    {
        $minimumAmount = self::SUPERMIND_REQUEST_MINIMUM_AMOUNT[$paymentMethod];

        $paymentTypeId = SupermindRequestPaymentMethod::getPaymentTypeId($paymentMethod);

        if (isset($this->config->get('supermind')['minimum_amount'][$paymentTypeId])) {
            $minimumAmount = $this->config->get('supermind')['minimum_amount'][$paymentTypeId];
        }

        return $minimumAmount;
    }
}
