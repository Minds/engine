<?php
namespace Minds\Core\Wire\SupportTiers\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Wire\SupportTiers\SupportTier;

/**
 * Class CurrenciesDelegate
 * @package Minds\Core\Wire\SupportTiers\Delegates
 */
class CurrenciesDelegate
{
    /** @var Config */
    private $config;

    /**
     * CurrenciesDelegate constructor.
     * @param $config
     */
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param SupportTier $supportTier
     * @return SupportTier
     */
    public function hydrate(SupportTier $supportTier): ?SupportTier
    {
        if (!$supportTier) {
            return null;
        }

        // Tokens

        $tokenExchangeRate = $this->config->get('token_exchange_rate') ?: 1.25;
        $supportTier->setTokens($supportTier->getUsd() / $tokenExchangeRate);

        // Return

        return $supportTier;
    }
}
