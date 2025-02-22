<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Traits\MagicAttributes;
use Brick\Math\BigDecimal;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setTokenSharePct(float $tokenSharePct)
 * @method float getTokenSharePct()
 * @method self setTotalLiquidityTokens(BigDecimal $totalLiquidityTokens)
 * @method BigDecimal getTotalLiquidityTokens()
 * @method self setUserLiquidityTokens(BigDecimal $userLiquidityTokens)
 * @method BigDecimal getUserLiquidityTokens()
 * @method self setProvidedLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getProvidedLiquidity()
 * @method self setYieldLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getYieldLiquidity()
 * @method self setTotalLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getTotalLiquidity()
 * @method self setShareOfLiquidity(LiquidityCurrencyValues $liquidity)
 * @method LiquidityCurrencyValues getShareOfLiquidity()
 * @method self setLiquiditySpotOptOut(bool $optOut)
 * @method bool getLiquiditySpotOptOut()
 * @method bool isLiquiditySpotOptOut()
 * @method self setLpPosition(BigDecimal $lpPosition)
 * @method BigDecimal getLpPosition()
 */
class LiquidityPositionSummary
{
    use MagicAttributes;

    /** @var string */
    protected $userGuid;

    /** @var float */
    protected $tokenSharePct;

    /** @var BigDecimal */
    protected $totalLiquidityTokens;

    /** @var BigDecimal */
    protected $userLiquidityTokens;

    /** @var LiquidityCurrencyValues */
    protected $providedLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $yieldLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $totalLiquidity;

    /** @var LiquidityCurrencyValues */
    protected $shareOfLiquidity;
    
    protected BigDecimal $lpPosition;

    /** @var bool */
    protected $liquiditySpotOptOut = false;

    /**
     * Public export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'user_guid' => (string) $this->userGuid,
            'token_share_pct' => $this->tokenSharePct,
            'total_liquidity_tokens' => $this->totalLiquidityTokens,
            'user_liquidity_tokens' => $this->userLiquidityTokens,
            'provided_liquidity' => $this->providedLiquidity->export(),
            'yield_liquidity' => $this->yieldLiquidity->export(),
            'total_liquidity' => $this->totalLiquidity->export(),
            'shareOf_liquidity' => $this->shareOfLiquidity->export(),
            'liquidity_spot_opt_out' => $this->liquiditySpotOptOut,
        ];
    }
}
