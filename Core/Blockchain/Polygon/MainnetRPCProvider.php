<?php

namespace Minds\Core\Blockchain\Polygon;


class MainnetRPCProvider
{
    public function __construct()
    {
    }

    /**
     * Converted from JSON using https://dataconverter.curiousconcept.com/
     * @return array
     */
    public function getProvider(): array
    {
        return [
            
                'url' => 'https://goerli.infura.io/v3/9aa3d95b3bc440fa88ea12eaa4456161',
                'chain_id' => 5
        ];
    }
}
