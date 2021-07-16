<?php
namespace Minds\Core\Blockchain\Uniswap;

use Brick\Math\BigDecimal;

/**
 * @method string getId()
 * @method self setId(string $id)
 * @method BigDecimal getLiquidityTokenBalance()
 * @method self setLiquidityTokenBalance(BigDecimal $liquidityTokenBalance)
 * @method UniswapPairEntity getPair()
 * @method self setPair(UniswapPairEntity $pair)
 */
class UniswapLiquidityPositionEntity implements UniswapEntityHasPairInterface, UniswapEntityInterface
{
    use UniswapBaseTrait;

    /** @var BigDecimal */
    protected $liquidityTokenBalance;

    /** @var UniswapPairEntity */
    protected $pair;

    /**
     * Returns the liquidirty token share
     * @return float
     */
    public function getLiquidityTokenShare(): float
    {
        if (!$this->pair) {
            return 0;
        }

        return $this->liquidityTokenBalance->dividedBy($this->pair->getTotalSupply())->toFloat();
    }

    /**
     * Builds from an array
     * @param array $data
     * @return UniswapLiquidityPositionEntity
     */
    public static function build(array $data): self
    {
        $instance = self::buildBase($data);
        $instance
            ->setLiquidityTokenBalance(BigDecimal::of($data['liquidityTokenBalance']))
            ->setPair(UniswapPairEntity::build($data['pair']));

        return $instance;
    }
}
