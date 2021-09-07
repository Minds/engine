<?php
namespace Minds\Core\Blockchain\Uniswap;

use Minds\Traits\MagicAttributes;

/**
 * @method string getId()
 * @method self setId(string $id)
 */
trait UniswapBaseTrait
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /**
     * Builds from an array
     * @param array $data
     * @return self
     */
    protected static function buildBase(array $data): self
    {
        $instance = new self();
        $instance->setId($data['id']);

        return $instance;
    }
}
