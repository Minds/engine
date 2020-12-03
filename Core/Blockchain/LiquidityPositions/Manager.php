<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Minds\Core\Blockchain\Uniswap\UniswapEntityHasPairInterface;
use Minds\Core\Blockchain\Uniswap\UniswapEntityInterface;
use Minds\Core\Blockchain\Uniswap\UniswapMintEntity;

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
                }, array_filter($uniswapUser->getLiquidityPositions(), [$this, 'uniswapApprovedPairsFilterFn']))
            );
        } catch (\InvalidArgumentException $e) {
            $userLiquidityTokens = BigDecimal::of(0);
        }

        // Filter out mints to be only approved pairs
        $approvedMints = array_filter($uniswapUser->getMints() ?? [], [$this, 'uniswapApprovedPairsFilterFn']);
        $approvedMintsUSD = BigDecimal::sum(...(array_map([$this, 'uniswapMintsToUSD'], $approvedMints) ?: [0]));
        $approvedMintsMINDS = BigDecimal::sum(...(array_map([$this, 'uniswapMintsToMINDS'], $approvedMints) ?: [0]));

        // Filter out burns to be only approved pairs
        $approvedBurns = array_filter($uniswapUser->getBurns() ?? [], [$this, 'uniswapApprovedPairsFilterFn']);
        $approvedBurnsUSD = BigDecimal::sum(...(array_map([$this, 'uniswapMintsToUSD'], $approvedBurns) ?: [0]));
        $approvedBurnsMINDS =  BigDecimal::sum(...(array_map([$this, 'uniswapMintsToMINDS'], $approvedBurns) ?: [0]));

        // Deduct burns from mints
        $liquidityUSD = $approvedMintsUSD->minus($approvedBurnsUSD);
        $liquidityMINDS = $approvedMintsMINDS->minus($approvedBurnsMINDS);

        $summary = new LiquidityPositionSummary();
        $summary->setTokenSharePct($userLiquidityTokens->dividedBy($totalLiquidityTokens, 4, RoundingMode::FLOOR)->toFloat())
            ->setTotalLiquidityTokens($totalLiquidityTokens)
            ->setUserLiquidityTokens($userLiquidityTokens)
            ->setLiquidityUSD($liquidityUSD)
            ->setLiquidityMINDS($liquidityMINDS);

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

    /**
     * To be used by PHP array_filter as a callback
     * @param UniswapEntityHasPairInterface $uniswapEntity
     * @return bool
     */
    private function uniswapApprovedPairsFilterFn(UniswapEntityHasPairInterface $uniswapEntity): bool
    {
        // We filter out to return only approved liquidity pairs
        return in_array($uniswapEntity->getPair()->getId(), $this->getApprorvedLiquidityPairIds(), true);
    }

    /**
     * To be used by PHP array_map as callback
     * @param UniswapMintEntity $uniswapMint
     * @return BigDecimal
     */
    private function uniswapMintsToUSD(UniswapMintEntity $uniswapMint): BigDecimal
    {
        return $uniswapMint->getAmountUSD();
    }

    /**
     * To be used by PHP array_map as callback
     * @param UniswapMintEntity $uniswapMint
     * @return BigDecimal
     */
    private function uniswapMintsToMINDS(UniswapMintEntity $uniswapMint): BigDecimal
    {
        // TODO: We should ideally be confirming amount0 is actually Minds.
        // However, for now all of our approved pairs do maintain this order
        // Verifying ->getPair()->getToken0()->getId() should be able to do this?
        return $uniswapMint->getAmount0();
    }
}
