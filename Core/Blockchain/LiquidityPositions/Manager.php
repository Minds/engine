<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class Manager
{
    /** @var Uniswap\Client */
    protected $uniswapClient;
    
    /** @var Config */
    protected $config;

    /** @var User */
    protected $user;

    public function __construct(Uniswap\Client $uniswapClient = null, Config $config = null)
    {
        $this->uniswapClient = $uniswapClient ?? Di::_()->get('Blockchain\Uniswap\Client');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Sets the user we are interacting with
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user): Manager
    {
        $manager = clone $this;
        $manager->user = $user;
        return $manager;
    }

    /**
     * Returns the summary of a users liquidity position (includes share)
     * @return LiquidityPositionSummary
     * @throws \Exception
     */
    public function getSummary(): LiquidityPositionSummary
    {
        if (!$this->user) {
            throw new \Exception("setUser(User \$user) must be called");
        }

        if (!$address = $this->user->getEthWallet()) {
            throw new \Exception("User must have an ETH wallet setup");
        }

        $uniswapUser = $this->uniswapClient->getUser($address);

        $pairs = $this->uniswapClient->getPairs($this->getApprorvedLiquidityPairIds());

        if ($pairs) {
            $totalLiquidityTokens = BigDecimal::sum(...array_map(function ($uniswapPair) {
                return $uniswapPair->getTotalSupply();
            }, $pairs));
        } else {
            $totalLiquidityTokens = BigDecimal::of(0);
        }

        try {
            $userLiquidityTokens = BigDecimal::sum(
                ...array_map(function ($uniswapLiquidityPosition) {
                    return $uniswapLiquidityPosition->getLiquidityTokenBalance();
                }, array_filter($uniswapUser->getLiquidityPositions(), function ($uniswapLiquidityPosition) {
                    // We filter out to return only approved liquidity pairs
                    return in_array($uniswapLiquidityPosition->getPair()->getId(), $this->getApprorvedLiquidityPairIds(), true);
                }))
            );
        } catch (\InvalidArgumentException $e) {
            $userLiquidityTokens = BigDecimal::of(0);
        }

        $summary = new LiquidityPositionSummary();
        $summary->setTokenSharePct($userLiquidityTokens->dividedBy($totalLiquidityTokens, 4, RoundingMode::FLOOR)->toFloat())
            ->setTotalLiquidityTokens($totalLiquidityTokens->toFloat())
            ->setUserLiquidityTokens($userLiquidityTokens->toFloat());
        
        return $summary;
    }

    /**
     * Returns approved liquidity pools
     * @return string[]
     */
    private function getApprorvedLiquidityPairIds(): array
    {
        return $this->config->get('blockchain')['liquidity_positions']['approved_pairs'];
    }
}
