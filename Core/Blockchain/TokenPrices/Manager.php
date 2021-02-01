<?php
namespace Minds\Core\Blockchain\TokenPrices;

use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\DivisionByZeroException;
use Minds\Core\EntitiesBuilder;

class Manager
{
    /** @var Uniswap\Client */
    protected $uniswapClient;
    
    /** @var Config */
    protected $config;

    public function __construct(
        Uniswap\Client $uniswapClient = null,
        Config $config = null
    ) {
        $this->uniswapClient = $uniswapClient ?? Di::_()->get('Blockchain\Uniswap\Client');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Returns an array of prices
     * @return array
     */
    public function getPrices(): array
    {
        $tokenAddress = $this->config->get('blockchain')['token_address'];
        $prices = $this->uniswapClient->getTokenUsdPrices($tokenAddress);

        return [
            'eth' => $prices['eth'],
            'minds' => $prices['token'],
        ];
    }
}
