<?php
namespace Minds\Core\Blockchain\Uniswap;

use Brick\Math\BigDecimal;

/**
 * @method string getId()
 * @method self setId(string $id)
 * @method BigDecimal getTotalSupply()
 * @method self setTotalSupply(BigDecimal $totalSupply)
 */
class UniswapPairEntity extends UniswapBaseEntity
{
    /** @var BigDecimal */
    protected $totalSupply;

    /**
     * Builds from an array
     * @param array $data
     */
    public static function build(array $data): UniswapEntityInterface
    {
        $instance = parent::build($data);
        $instance->setTotalSupply(BigDecimal::of($data['totalSupply']));
       
        return $instance;
    }
}
