<?php
namespace Minds\Core\Blockchain\Uniswap;

/**
 * @method string getId()
 * @method self setId(string $id)
 * @method float getUsdSwaped()
 * @method self setUsdSwaped(float $usdSwaped)
 * @method UniswapLiquidityPositionEntity[] getLiquidityPositions()
 * @method self setLiquidityPositions(UniswapLiquidityPositionEntity[] $liquidityPositions)
 */
class UniswapUserEntity extends UniswapBaseEntity
{
    /** @var float */
    protected $usdSwaped;

    /** @var UniswapLiquidityPositionEntity[] */
    protected $liquidityPositions;
}
