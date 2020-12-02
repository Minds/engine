<?php
namespace Minds\Core\Blockchain\Uniswap;

use Minds\Traits\MagicAttributes;

/**
 * @method string getId()
 * @method self setId(string $id)
 */
class UniswapBaseEntity
{
    use MagicAttributes;

    /** @var string */
    protected $id;
}
