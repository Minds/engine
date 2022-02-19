<?php

namespace Minds\Core\Blockchain\Polygon;

class PolygonContractsGoerli
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
                            'name' => 'symbol',
                            'type' => 'string'
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
                    'type' => 'event'
                ],
                3 => [
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
                ],
                4 => [
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
                ],
                5 => [
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
                ],
                6 => [
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
                ],
                7 => [
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
                ],
                8 => [
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
                    'type' => 'function',
                ],
                9 => [
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
                        ]
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
                ],
                10 => [
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
                        ]
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
                11 => [
                    'inputs' => [
                        0 => [
                            'internalType' => 'address',
                            'name' => 'spender',
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
                    'type' => 'function',
                ],
                12 => [
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
                    'type' => 'function',
                ],
                13 => [
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
                    'type' => 'function',
                ]
            ]

        ];
    }
}
