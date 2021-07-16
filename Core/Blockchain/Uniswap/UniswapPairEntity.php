<?php
namespace Minds\Core\Blockchain\Uniswap;

use Brick\Math\BigDecimal;

/**
 * @method string getId()
 * @method self setId(string $id)
 * @method BigDecimal getTotalSupply()
 * @method self setTotalSupply(BigDecimal $totalSupply)
 * @method BigDecimal getReserve0()
 * @method self setReserve0(BigDecimal $reserve0)
 * @method BigDecimal getReserve1()
 * @method self setReserve1(BigDecimal $reserve1)
 * @method BigDecimal getReserveUSD()
 * @method self setReserveUSD(BigDecimal $reserveUSD)
 */
class UniswapPairEntity implements UniswapEntityInterface
{
    use UniswapBaseTrait;

    /** @var BigDecimal */
    protected $totalSupply;

    /** @var BigDecimal */
    protected $reserve0;

    /** @var BigDecimal */
    protected $reserve1;

    /** @var BigDecimal */
    protected $reserveUSD;

    /** @var BigDecimal */
    protected $volumeToken0;

    /** @var BigDecimal */
    protected $volumeToken1;

    /** @var BigDecimal */
    protected $untrackedVolumeUSD;

    /**
     * Builds from an arrayπ
     * @param array $data
     */
    public static function build(array $data): self
    {
        $instance = self::buildBase($data);
        $instance->setTotalSupply(BigDecimal::of($data['totalSupply']))
            ->setReserveUSD(BigDecimal::of($data['reserveUSD']))
            ->setReserve0(BigDecimal::of($data['reserve0']))
            ->setReserve1(BigDecimal::of($data['reserve1']))
            ->setVolumeToken0(BigDecimal::of($data['volumeToken0'] ?? 0))
            ->setVolumeToken1(BigDecimal::of($data['volumeToken1'] ?? 0))
            ->setUntrackedVolumeUSD(BigDecimal::of($data['untrackedVolumeUSD'] ?? 0));
       
        return $instance;
    }
}
