<?php

namespace Minds\Core\Supermind;

use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;

/**
 * Responsible to handle the allowed minimum Supermind request payment amount
 */
class SupermindRequestMinimumPaymentAmount
{
    /**
     * @const float Defines the minimum allowed amount for a Supermind requests
     */
    private const SUPERMIND_REQUEST_MINIMUM_AMOUNT = 10.00;

    public function __construct(private ?MindsConfig $mindsConfig = null)
    {
        $this->mindsConfig ??= Di::_()->get("Config");
    }

    public function getMinimumAllowedAmount(): float
    {
        $minimumAmount = self::SUPERMIND_REQUEST_MINIMUM_AMOUNT;
        if (isset($this->config->get('supermind')['minimum_amount'])) {
            $minimumAmount = $this->config->get('supermind')['minimum_amount'];
        }

        // TODO: Add check for user settings override

        return $minimumAmount;
    }

    public function isAmountAllowed()
    {
    }
}
