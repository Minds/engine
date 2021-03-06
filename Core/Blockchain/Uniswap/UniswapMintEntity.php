<?php
namespace Minds\Core\Blockchain\Uniswap;

use Brick\Math\BigDecimal;

/**
 * @method string getId()
 * @method self setId(string $id)
 * @method string getTo()
 * @method self setTo(string $to)
 * @method BigDecimal getLiquidity()
 * @method self setLiquidity(BigDecimal $liquidity)
 * @method BigDecimal getAmount0()
 * @method self setAmount0(BigDecimal $amount0)
 * @method BigDecimal getAmount1()
 * @method self setAmount1(BigDecimal $amount1)
 * @method BigDecimal getAmountUSD()
 * @method self setAmountUSD(BigDecimal $amountUSD)
 * @method UniswapPairEntity getPair()
 * @method self setPair(UniswapPairEntity $pair)
 */
class UniswapMintEntity implements UniswapEntityHasPairInterface, UniswapEntityInterface
{
    use UniswapBaseTrait;

    /** @var BigDecimal */
    protected $liquidity;

    /** @var string */
    protected $to;

    /** @var BigDecimal */
    protected $amount0;

    /** @var BigDecimal */
    protected $amount1;

    /** @var BigDecimal */
    protected $amountUSD;

    /** @var UniswapPairEntity */
    protected $pair;

    public static function build(array $data): self
    {
        $instance = self::buildBase($data);
        $instance
            ->setTo($data['to'])
            ->setAmount0(BigDecimal::of($data['amount0']))
            ->setAmount1(BigDecimal::of($data['amount1']))
            ->setAmountUSD(BigDecimal::of($data['amountUSD']))
            ->setLiquidity(BigDecimal::of($data['liquidity'] ?? 0));

        $instance->setPair(UniswapPairEntity::build($data['pair']));

        return $instance;
    }
}
