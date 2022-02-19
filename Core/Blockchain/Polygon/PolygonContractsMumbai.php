<?php

namespace Minds\Core\Blockchain\Polygon;

class PolygonContractsMumbai
{
    public function __construct()
    {
    }

    /**
     * Converted from JSON using https://dataconverter.curiousconcept.com/
     * @return array
     */
    public function getABIs(): array
    {
        return [
            'abi' =>
            [
                0 =>
                [
                    'inputs' => [
                        0 => [
                            'internalType' => 'string',
                            'name' => 'name',
                            'type' => 'string'
                        ],
                        1 => [
                            'internalType' => 'string',
                            'name' => 'symbol_',
                            'type' => 'string'
                        ],
                        2 => [
                            'internalType' => 'uint8',
                            'name' => 'decimals_',
                            'type' => 'uint8'
                        ],
                        3 => [
                            'internalType' => 'address',
                            'name' => 'childChainManager',
                            'type' => 'address'
                        ]
                    ],
                    'stateMutability' => 'nonpayable',
                    'type' => 'constructor'
                ],
                1  =>
                [
                    'anonymous' => false,
                    'inputs' => [
                        0 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'owner',
                            'type' => 'address'
                        ],
                        1 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'spender',
                            'type' => 'address'
                        ],
                        2 => [
                            'indexed' => false,
                            'internalType' => 'uint256',
                            'name' => 'value',
                            'type' => 'uint256'
                        ],
                    ],
                    'name' => 'Approval',
                    'type' => 'event'
                ],
                2 => [
                    'anonymous' => false,
                    'inputs' => [
                        0 => [
                            'indexed' => false,
                            'internalType' => 'address',
                            'name' => 'userAddress',
                            'type' => 'address'
                        ],
                        1 => [
                            'indexed' => false,
                            'internalType' => 'address payable',
                            'name' => 'relayerAddress',
                            'type' => 'address'
                        ],
                        2 => [
                            'indexed' => false,
                            'internalType' => 'bytes',
                            'name' => 'functionSignature',
                            'type' => 'bytes'
                        ]
                    ],
                    'name' => 'MetaTransactionExecuted',
                    'type' => 'event'
                ],
                3 => [
                    'anonymous' => false,
                    'inputs' => [
                        0 => [
                            'indexed' => true,
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'indexed' => true,
                            'internalType' => 'bytes32',
                            'name' => 'previousAdminRole',
                            'type' => 'bytes32'
                        ],
                        2 => [
                            'indexed' => true,
                            'internalType' => 'bytes32',
                            'name' => 'newAdminRole',
                            'type' => 'bytes32'
                        ]
                    ],
                    'name' => 'RoleAdminChanged',
                    'type' => 'event',
                ],
                4 => [
                    'anonymous' => false,
                    'inputs' => [
                        0 => [
                            'indexed' => true,
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ],
                        2 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'sender',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'RoleGranted',
                    'type' => 'event',
                ],
                5 => [
                    'anonymous' => false,
                    'inputs' => [
                        0 => [
                            'indexed' => true,
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ],
                        2 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'sender',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'RoleRevoked',
                    'type' => 'event',
                ],
                6 => [
                    'anonymous' => false,
                    'inputs' => [
                        0 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'from',
                            'type' => 'address'
                        ],
                        1 => [
                            'indexed' => true,
                            'internalType' => 'address',
                            'name' => 'to',
                            'type' => 'address'
                        ],
                        2 => [
                            'indexed' => false,
                            'internalType' => 'uint256',
                            'name' => 'value',
                            'type' => 'uint256'
                        ]
                    ],
                    'name' => 'Transfer',
                    'type' => 'event',
                ],
                7 => [
                    'inputs' => [],
                    'name' => 'CHILD_CHAIN_ID',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => '',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                8 => [
                    'inputs' => [],
                    'name' => 'CHILD_CHAIN_ID_BYTES',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes',
                            'name' => '',
                            'type' => 'bytes'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                9 => [
                    'inputs' => [],
                    'name' => 'DEFAULT_ADMIN_ROLE',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => '',
                            'type' => 'bytes32'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                10 => [
                    'inputs' => [],
                    'name' => 'DEPOSITOR_ROLE',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => '',
                            'type' => 'bytes32'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                11 => [
                    'inputs' => [],
                    'name' => 'ERC712_VERSION',
                    'outputs' => [
                        0 => [
                            'internalType' => 'string',
                            'name' => '',
                            'type' => 'string'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                12 => [
                    'inputs' => [],
                    'name' => 'ROOT_CHAIN_ID',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => '',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                13 => [
                    'inputs' => [],
                    'name' => 'ROOT_CHAIN_ID_BYTES',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes',
                            'name' => '',
                            'type' => 'bytes'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                14 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'owner',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'address',
                            'name' => 'spender',
                            'type' => 'address'
                        ],

                    ],
                    'name' => 'allowance',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => '',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                15 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'spender',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'uint256',
                            'name' => 'amount',
                            'type' => 'uint256'
                        ],

                    ],
                    'name' => 'approve',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bool',
                            'name' => '',
                            'type' => 'bool'
                        ]
                    ],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function',
                ],
                16 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ]

                    ],
                    'name' => 'balanceOf',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => '',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                17 => [
                    'inputs' => [],
                    'name' => 'decimals',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint8',
                            'name' => '',
                            'type' => 'uint8'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                18 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'spender',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'uint256',
                            'name' => 'subtractedValue',
                            'type' => 'uint256'
                        ]

                    ],
                    'name' => 'decreaseAllowance',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bool',
                            'name' => '',
                            'type' => 'bool'
                        ]
                    ],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                19 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'userAddress',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'bytes',
                            'name' => 'functionSignature',
                            'type' => 'bytes'
                        ],
                        2 => [
                            'internalType' => 'bytes32',
                            'name' => 'sigR',
                            'type' => 'bytes32'
                        ],
                        3 => [
                            'internalType' => 'bytes32',
                            'name' => 'sigS',
                            'type' => 'bytes32'
                        ],
                        4 => [
                            'internalType' => 'uint8',
                            'name' => 'sigV',
                            'type' => 'uint8'
                        ]

                    ],
                    'name' => 'executeMetaTransaction',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes',
                            'name' => '',
                            'type' => 'bytes'
                        ]
                    ],
                    'stateMutability' => 'payable',
                    'type' => 'function',
                    'payable' => true
                ],
                20 => [
                    'inputs' => [],
                    'name' => 'getChainId',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => '',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'pure',
                    'type' => 'function',
                    'constant' => true
                ],
                21 => [
                    'inputs' => [],
                    'name' => 'getDomainSeperator',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => '',
                            'type' => 'bytes32'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                22 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'user',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'getNonce',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => 'nonce',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                23 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ]
                    ],
                    'name' => 'getRoleAdmin',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => '',
                            'type' => 'bytes32'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                24 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'internalType' => 'uint256',
                            'name' => 'index',
                            'type' => 'uint256'
                        ]
                    ],
                    'name' => 'getRoleMember',
                    'outputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => '',
                            'type' => 'address'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                25 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'grantRole',
                    'outputs' => [],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                26 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'hasRole',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bool',
                            'name' => '',
                            'type' => 'bool'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                27 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'spender',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'uint256',
                            'name' => 'addedValue',
                            'type' => 'uint256'
                        ]
                    ],
                    'name' => 'increaseAllowance',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bool',
                            'name' => '',
                            'type' => 'bool'
                        ]
                    ],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                28 => [
                    'inputs' => [],
                    'name' => 'name',
                    'outputs' => [
                        0 => [
                            'internalType' => 'string',
                            'name' => '',
                            'type' => 'string'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true,
                ],
                29 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'renounceRole',
                    'outputs' => [],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                30 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'bytes32',
                            'name' => 'role',
                            'type' => 'bytes32'
                        ],
                        1 => [
                            'internalType' => 'address',
                            'name' => 'account',
                            'type' => 'address'
                        ]
                    ],
                    'name' => 'revokeRole',
                    'outputs' => [],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                31 => [
                    'inputs' => [],
                    'name' => 'symbol',
                    'outputs' => [
                        0 => [
                            'internalType' => 'string',
                            'name' => '',
                            'type' => 'string'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                32 => [
                    'inputs' => [],
                    'name' => 'totalSupply',
                    'outputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => '',
                            'type' => 'uint256'
                        ]
                    ],
                    'stateMutability' => 'view',
                    'type' => 'function',
                    'constant' => true
                ],
                33 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'recipient',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'uint256',
                            'name' => 'amount',
                            'type' => 'uint256'
                        ]
                    ],
                    'name' => 'transfer',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bool',
                            'name' => '',
                            'type' => 'bool'
                        ]
                    ],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                34 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'sender',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'address',
                            'name' => 'recipient',
                            'type' => 'address'
                        ],
                        2 => [
                            'internalType' => 'uint256',
                            'name' => 'amount',
                            'type' => 'uint256'
                        ]
                    ],
                    'name' => 'transferFrom',
                    'outputs' => [
                        0 => [
                            'internalType' => 'bool',
                            'name' => '',
                            'type' => 'bool'
                        ]
                    ],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                35 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'user',
                            'type' => 'address'
                        ],
                        1 => [
                            'internalType' => 'bytes',
                            'name' => 'depositData',
                            'type' => 'bytes'
                        ]
                    ],
                    'name' => 'deposit',
                    'outputs' => [],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
                36 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'uint256',
                            'name' => 'amount',
                            'type' => 'uint256'
                        ]
                    ],
                    'name' => 'withdraw',
                    'outputs' => [],
                    'stateMutability' => 'nonpayable',
                    'type' => 'function'
                ],
            ]

        ];
    }
}
