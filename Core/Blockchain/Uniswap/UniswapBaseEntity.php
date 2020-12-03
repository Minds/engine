<?php
namespace Minds\Core\Blockchain\Uniswap;

use Minds\Traits\MagicAttributes;

/**
 * @method string getId()
 * @method self setId(string $id)
 */
class UniswapBaseEntity implements UniswapEntityInterface
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /**
     * Builds from an array
     * @param array $data
     * @return UniswapBaseEntity
     */
    public static function build(array $data): UniswapEntityInterface
    {
        $instance = new static();
        $instance->setId($data['id']);

        return $instance;
    }
}
