<?php

namespace Minds\Core\Boost\Network;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;

/**
 * Holds rates for boost impressions versus a defined monetary unit.
 */
class Rates
{
    /** @var array - boost config */
    private $boostConfig = [];

    /**
     * Constructor.
     * @param Config|null $config
     * @throws ServerErrorException - if boost settings are misconfigured.
     */
    public function __construct(
        private ?Config $config = null,
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->boostConfig = $this->getConfig();
    }

    /**
     * Get rate for USD expressed as how many impressions $1 gets.
     * e.g. with a rate of 1000, $1 is 1000 impressions.
     * @return int - rate of impressions per $1.
     */
    public function getUsdRate(): int
    {
        return isset($this->boostConfig['cash_impression_rate']) ? $this->boostConfig['cash_impression_rate'] : 1000;
    }

    /**
     * Get token rate for MINDS expressed as how many impressions 1 token gets.
     * e.g. with a rate of 1000, 1 MINDS is 1000 impressions.
     * @return int - rate of impressions per 1 MINDS.
     */
    public function getTokenRate(): int
    {
        return isset($this->boostConfig['token_impression_rate']) ? $this->boostConfig['token_impression_rate'] : 1000;
    }

    /**
     * Get network boost config.
     * @throws ServerErrorException - if boost settings are misconfigured.
     * @return array
     */
    private function getConfig(): array
    {
        return $this->config->get('boost')['network'] ?? throw new ServerErrorException('Misconfigured boost settings');
    }
}
