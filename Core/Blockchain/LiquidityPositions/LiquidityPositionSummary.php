<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Traits\MagicAttributes;
use Brick\Math\BigDecimal;

/**
 * @method self setTokenSharePct(float $tokenSharePct)
 * @method float getTokenSharePct()
 * @method self setTotalLiquidityTokens(BigDecimal $totalLiquidityTokens)
 * @method BigDecimal getTotalLiquidityTokens()
 * @method self setUserLiquidityTokens(BigDecimal $userLiquidityTokens)
 * @method BigDecimal getUserLiquidityTokens()
 * @method self setProvidedLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getProvidedLiquidity()
 * @method self setCurrentLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getCurrentLiquidity()
 * @method self setYieldLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getYieldLiquidity()
 * @method self setTotalLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getTotalLiquidity()
 * @method self setShareOfLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getShareOfLiquidity()
 */
class LiquidityPositionSummary
{
    use MagicAttributes;

    /** @var float */
    protected $tokenSharePct;

    /** @var BigDecimal */
    protected $totalLiquidityTokens;

    /** @var BigDecimal */
    protected $userLiquidityTokens;

    /** @var LiquidityCurrencyValues */
    protected $providedLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $currentLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $yieldLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $totalLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $shareOfLiquidity;

    /**
     * Public export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'token_share_pct' => $this->tokenSharePct,
            'total_liquidity_tokens' => $this->totalLiquidityTokens,
            'user_liquidity_tokens' => $this->userLiquidityTokens,
            'provided_liquidity' => $this->providedLiquidity->export(),
            'current_liquidity' => $this->currentLiquidity->export(),
            'yield_liquidity' => $this->yieldLiquidity->export(),
            'total_liquidity' => $this->totalLiquidity->export(),
            'shareOf_liquidity' => $this->shareOfLiquidity->export(),
        ];
    }
}
