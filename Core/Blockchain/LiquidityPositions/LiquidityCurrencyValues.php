<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Traits\MagicAttributes;
use Brick\Math\BigDecimal;

/**
 * @method self setUsd(BigDecimal $usd)
 * @method BigDecimal getUsd()
 * @method self setMinds(BigDecimal $minds)
 * @method BigDecimal getMinds()
 */
class LiquidityCurrencyValues
{
    use MagicAttributes;

    /** @var BigDecimal */
    protected $usd;

    /** @var BigDecimal */
    protected $minds;

    /**
     * Public export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'USD' => $this->usd,
            'MINDS' => $this->minds,
        ];
    }
}
