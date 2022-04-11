<?php

namespace Minds\Core\Blockchain\Polygon;


class PolygonServiceConstants
{
    public function __construct()
    {
    }

    /**
     * Converted from JSON using https://dataconverter.curiousconcept.com/
     * @return array
     */
    public function getConstants(): array
    {
        return [
            'MAINNET_RPC_URL' => 'https://goerli.infura.io/v3/9aa3d95b3bc440fa88ea12eaa4456161',
            'POLYGON_RPC_URL' => 'https://rpc-endpoints.superfluid.dev/mumbai',
            'FROM_POLYGON_BLOCK' => 22642885,
            'FROM_MAINNET_BLOCK' => 5946883,
            'MAINNET_CHAIN_ID' => 5,
            'POLYGON_CHAIN_ID' => 80001,
            'MIND_TOKEN_ADDRESS' => '0x8bda9f5c33fbcb04ea176ea5bc1f5102e934257f',
            'MIND_CHILD_TOKEN_ADDRESS' => '0x22E993D9108DbDe9F32553C3fD0A404aCD2B7150',
            'ERC20_PREDICATE_ADDRESS' => '0xdD6596F2029e6233DEFfaCa316e6A95217d4Dc34',
            'ADDRESS_ZERO' => '0x0000000000000000000000000000000000000000',
            'ERC20_PREDICATE_ABI' => ['event LockedERC20(address indexed depositor, address indexed depositReceiver, address indexed rootToken, uint256 amount)'],
            'ROOT_CHAIN_MANAGER_ABI' => [
                'function depositFor(address user, address rootToken, bytes depositData)',
                'function exit(bytes inputData)',
                'function processedExits(bytes32) view returns (bool)',
                'function tokenToType(address) view returns (bytes32)',
                'function typeToPredicate(bytes32) view returns (address)',
            ],
            'ERC20_ABI' => [
                'event Transfer(address indexed from, address indexed to, uint256 value)',
                'function balanceOf(address account) view returns (uint256)',
                'function allowance(address owner, address spender) view returns (uint256)',
            ],
            'ROOT_CHAIN_ABI' =>  [
                'function currentHeaderBlock() view returns (uint256)',
                'function getLastChildBlock() view returns (uint256)',
            ]
        ];
    }
}
