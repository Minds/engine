<?php

namespace Minds\Core\Blockchain\Polygon;

class PolygonRPCProvider
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
            
                'url' => 'https://rpc-endpoints.superfluid.dev/mumbai',
                'chain_id' => 80001
        ];
    }
}
