<?php
namespace Minds\Core\Blockchain\Uniswap;

interface UniswapEntityInterface
{
    /**
     * @param array $data
     * @return UniswapEntityInterface
     */
    public static function build(array $data): self;
}
