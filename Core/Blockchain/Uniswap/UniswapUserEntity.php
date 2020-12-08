<?php
namespace Minds\Core\Blockchain\Uniswap;

/**
 * @method string getId()
 * @method self setId(string $id)
 * @method float getUsdSwapped()
 * @method self setUsdSwapped(float $usdSwapped)
 * @method UniswapLiquidityPositionEntity[] getLiquidityPositions()
 * @method self setLiquidityPositions(UniswapLiquidityPositionEntity[] $liquidityPositions)
 * @method UniswapMintEntity[] getMints()
 * @method self setMints(UniswapMints[] $mints)
 * @method UniswapBurnEntity[] getBurns()
 * @method self setBurns(UniswapMints[] $burns)
 */
class UniswapUserEntity extends UniswapBaseEntity
{
    /** @var float */
    protected $usdSwapped;

    /** @var UniswapLiquidityPositionEntity[] */
    protected $liquidityPositions;

    /** @var UniswapMintEntity[] */
    protected $mints;

    /** @var UniswapBurnEntity[] */
    protected $burns;

    /**
     * Builds from an array
     * @param array $data
     */
    public static function build(array $data): UniswapEntityInterface
    {
        $instance = parent::build($data);
        $instance->setUsdWapped($data['usdSwapped']);
       
        return $instance;
    }
}
