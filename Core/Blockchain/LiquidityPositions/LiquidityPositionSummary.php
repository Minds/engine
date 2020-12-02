<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Traits\MagicAttributes;

/**
 * @method self setTokenSharePct(float $tokenSharePct)
 * @method float getTokenSharePct()
 * @method self setTotalLiquidityTokens(float $totalLiquidityTokens)
 * @method float getTotalLiquidityTokens()
 * @method float getUserLiquidityTokens()
 * @method self setUserLiquidityTokens(float $userLiquidityTokens)
 */
class LiquidityPositionSummary
{
    use MagicAttributes;

    /** @var float */
    protected $tokenSharePct;

    protected $totalLiquidityTokens;

    protected $userLiquidityTokens;

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
        ];
    }
}
