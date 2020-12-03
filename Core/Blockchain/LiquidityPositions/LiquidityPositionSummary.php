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
 * @method self setLiquidityUSD(BigDecimal $liquidityUSD)
 * @method BigDecimal getLiquidityUSD()
 * @method self setLiquidityMINDS(BigDecimal $liquidityMINDS)
 * @method BigDecimal getLiquidityMINDS()
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

    /** @var BigDecimal */
    protected $liquidityUSD;

    /** @var BigDecimal */
    protected $liquidityMINDS;

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
            'liquidity' => [
                'USD' => $this->liquidityUSD,
                'MINDS' => $this->liquidityMINDS,
            ]
        ];
    }
}
