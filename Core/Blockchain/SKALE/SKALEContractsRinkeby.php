<?php

namespace Minds\Core\Blockchain\SKALE;

class SKALEContractsRinkeby
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
            'message_proxy_mainnet_address' => '0x90d12C0F88f5f3776e5aEB68F11F573B96EFc351',
            'message_proxy_mainnet_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'GasCostMessageHeaderWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'GasCostMessageWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'GasLimitWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'OutgoingMessage',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'dstChainHash',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'msgCounter',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'srcContract',
                    'indexed' => true,
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'dstContract',
                    'indexed' => false,
                  ],
                  4 =>
                  [
                    'type' => 'bytes',
                    'name' => 'data',
                    'indexed' => false,
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'PostMessageError',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'msgCounter',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes',
                    'name' => 'message',
                    'indexed' => false,
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              6 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              7 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'CHAIN_CONNECTOR_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'CONSTANT_SETTER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'EXTRA_CONTRACT_REGISTRAR_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_HASH',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'MESSAGES_LENGTH',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'addConnectedChain',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'communityPool',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'connectedChains',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'incomingMessageCounter',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'outgoingMessageCounter',
                  ],
                  2 =>
                  [
                    'type' => 'bool',
                    'name' => 'inited',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'gasLimit',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'getIncomingMessagesCounter',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'fromSchainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'getOutgoingMessagesCounter',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'targetSchainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'headerMessageGasCost',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'initializeMessageProxy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newGasLimit',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'isConnectedChain',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'isContractRegistered',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractAddress',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              32 =>
              [
                'type' => 'function',
                'name' => 'messageGasCost',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              33 =>
              [
                'type' => 'function',
                'name' => 'postIncomingMessages',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'fromSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'startingCounter',
                  ],
                  2 =>
                  [
                    'type' => 'tuple[]',
                    'name' => 'messages',
                    'components' =>
                    [
                      0 =>
                      [
                        'type' => 'address',
                        'name' => 'sender',
                      ],
                      1 =>
                      [
                        'type' => 'address',
                        'name' => 'destinationContract',
                      ],
                      2 =>
                      [
                        'type' => 'bytes',
                        'name' => 'data',
                      ],
                    ],
                  ],
                  3 =>
                  [
                    'type' => 'tuple',
                    'name' => 'sign',
                    'components' =>
                    [
                      0 =>
                      [
                        'type' => 'uint256[2]',
                        'name' => 'blsSignature',
                      ],
                      1 =>
                      [
                        'type' => 'uint256',
                        'name' => 'hashA',
                      ],
                      2 =>
                      [
                        'type' => 'uint256',
                        'name' => 'hashB',
                      ],
                      3 =>
                      [
                        'type' => 'uint256',
                        'name' => 'counter',
                      ],
                    ],
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              34 =>
              [
                'type' => 'function',
                'name' => 'postOutgoingMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'targetChainHash',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'targetContract',
                  ],
                  2 =>
                  [
                    'type' => 'bytes',
                    'name' => 'data',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              35 =>
              [
                'type' => 'function',
                'name' => 'registerExtraContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'extraContract',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              36 =>
              [
                'type' => 'function',
                'name' => 'registerExtraContractForAll',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'extraContract',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              37 =>
              [
                'type' => 'function',
                'name' => 'registryContracts',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              38 =>
              [
                'type' => 'function',
                'name' => 'removeConnectedChain',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              39 =>
              [
                'type' => 'function',
                'name' => 'removeExtraContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'extraContract',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              40 =>
              [
                'type' => 'function',
                'name' => 'removeExtraContractForAll',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'extraContract',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              41 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              42 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              43 =>
              [
                'type' => 'function',
                'name' => 'setCommunityPool',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityPoolAddress',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              44 =>
              [
                'type' => 'function',
                'name' => 'setNewGasLimit',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newGasLimit',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              45 =>
              [
                'type' => 'function',
                'name' => 'setNewHeaderMessageGasCost',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newHeaderMessageGasCost',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              46 =>
              [
                'type' => 'function',
                'name' => 'setNewMessageGasCost',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newMessageGasCost',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              47 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
            ],
            'linker_address' => '0x3bF2C2109C9f1DF1622D04D275D261De456d45c1',
            'linker_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'function',
                'name' => 'LINKER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'addSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractReceiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              6 =>
              [
                'type' => 'function',
                'name' => 'allowInterchainConnections',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'connectSchain',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address[]',
                    'name' => 'schainContracts',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'disconnectSchain',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'hasMainnetContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'mainnetContract',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'hasSchain',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => 'connected',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'hasSchainContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'messageProxyValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newContractManagerOfSkaleManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'interchainConnections',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'isNotKilled',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'kill',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'messageProxy',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'registerMainnetContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newMainnetContract',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'removeMainnetContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'mainnetContract',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'removeSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'schainLinks',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'statuses',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint8',
                    'name' => '',
                  ],
                ],
              ],
              32 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
            ],
            'community_pool_address' => '0xDD67f7bF39bCbcBC0146141Ab7722765911c8E90',
            'community_pool_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'MinTransactionGasWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'function',
                'name' => 'CONSTANT_SETTER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              6 =>
              [
                'type' => 'function',
                'name' => 'LINKER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'activeUsers',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'addSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractReceiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'getBalance',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'user',
                  ],
                  1 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'hasSchainContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'linker',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'messageProxyValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newContractManagerOfSkaleManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'messageProxy',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'minTransactionGas',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'rechargeUserWallet',
                'constant' => false,
                'stateMutability' => 'payable',
                'payable' => true,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                      'type' => 'address',
                      'name' => 'user',
                  ]
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'refundGasByUser',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'node',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'user',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => 'gas',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'removeSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'schainLinks',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'setMinTransactionGas',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newMinTransactionGas',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'withdrawFunds',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
            ],
            'deposit_box_eth_address' => '0x08473e763d6e6e2Fc634d718D5A043649F38bfAf',
            'deposit_box_eth_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'function',
                'name' => 'DEPOSIT_BOX_MANAGER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'LINKER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              6 =>
              [
                'type' => 'function',
                'name' => 'addSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractReceiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'approveTransfers',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'deposit',
                'constant' => false,
                'stateMutability' => 'payable',
                'payable' => true,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'to',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'disableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'enableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'getFunds',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'receiver',
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'getMyEth',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'hasSchainContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'linkerValue',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'messageProxyValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newContractManagerOfSkaleManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'isWhitelisted',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'linker',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'messageProxy',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'postMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  2 =>
                  [
                    'type' => 'bytes',
                    'name' => 'data',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'removeSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'schainLinks',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              32 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              33 =>
              [
                'type' => 'function',
                'name' => 'transferredAmount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
            ],
            'deposit_box_erc20_address' => '0x706190B6DfAa002abe1f334f82cc0de81626f343',
            'deposit_box_erc20_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC20TokenAdded',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                    'indexed' => true,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC20TokenReady',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                    'indexed' => false,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              6 =>
              [
                'type' => 'function',
                'name' => 'DEPOSIT_BOX_MANAGER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'LINKER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'addERC20TokenByOwner',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'addSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractReceiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'depositERC20',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'to',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'disableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'enableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'getFunds',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'receiver',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'getSchainToERC20',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'hasSchainContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'linkerValue',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'messageProxyValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newContractManagerOfSkaleManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'isWhitelisted',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'linker',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'messageProxy',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'postMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  2 =>
                  [
                    'type' => 'bytes',
                    'name' => 'data',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'removeSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              32 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              33 =>
              [
                'type' => 'function',
                'name' => 'schainLinks',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              34 =>
              [
                'type' => 'function',
                'name' => 'schainToERC20',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              35 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              36 =>
              [
                'type' => 'function',
                'name' => 'transferredAmount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
            ],
            'deposit_box_erc721_address' => '0x38f65677A112E67C816d5dA001BEEc977D85343E',
            'deposit_box_erc721_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC721TokenAdded',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                    'indexed' => true,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC721TokenReady',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'tokenId',
                    'indexed' => false,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              6 =>
              [
                'type' => 'function',
                'name' => 'DEPOSIT_BOX_MANAGER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'LINKER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'addERC721TokenByOwner',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'addSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractReceiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'depositERC721',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'to',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => 'tokenId',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'disableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'enableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'getFunds',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'receiver',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => 'tokenId',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'getSchainToERC721',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'hasSchainContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'linkerValue',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'messageProxyValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newContractManagerOfSkaleManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'isWhitelisted',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'linker',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'messageProxy',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'postMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  2 =>
                  [
                    'type' => 'bytes',
                    'name' => 'data',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'removeSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              32 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              33 =>
              [
                'type' => 'function',
                'name' => 'schainLinks',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              34 =>
              [
                'type' => 'function',
                'name' => 'schainToERC721',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              35 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              36 =>
              [
                'type' => 'function',
                'name' => 'transferredAmount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
            ],
            'deposit_box_erc1155_address' => '0x22F4Ad8F8574293975193A507d9E6C8578f487BD',
            'deposit_box_erc1155_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC1155TokenAdded',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                    'indexed' => true,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC1155TokenReady',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'ids',
                    'indexed' => false,
                  ],
                  2 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'amounts',
                    'indexed' => false,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleAdminChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'newAdminRole',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleGranted',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              4 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'RoleRevoked',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                    'indexed' => true,
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'DEFAULT_ADMIN_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              6 =>
              [
                'type' => 'function',
                'name' => 'DEPOSIT_BOX_MANAGER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'LINKER_ROLE',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'addERC1155TokenByOwner',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              9 =>
              [
                'type' => 'function',
                'name' => 'addSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractReceiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'contractManagerOfSkaleManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'depositERC1155',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'to',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => 'id',
                  ],
                  4 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'depositERC1155Batch',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'to',
                  ],
                  3 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'ids',
                  ],
                  4 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'amounts',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'disableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'enableWhitelist',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'getFunds',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'receiver',
                  ],
                  3 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'ids',
                  ],
                  4 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'amounts',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'getRoleAdmin',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'getRoleMember',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'index',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'getRoleMemberCount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'getSchainToERC1155',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              20 =>
              [
                'type' => 'function',
                'name' => 'grantRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'hasRole',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'hasSchainContract',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractManagerOfSkaleManagerValue',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'linkerValue',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'messageProxyValue',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newContractManagerOfSkaleManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              26 =>
              [
                'type' => 'function',
                'name' => 'isSchainOwner',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  1 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              27 =>
              [
                'type' => 'function',
                'name' => 'isWhitelisted',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              28 =>
              [
                'type' => 'function',
                'name' => 'linker',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'messageProxy',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'onERC1155BatchReceived',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'operator',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                  2 =>
                  [
                    'type' => 'uint256[]',
                    'name' => '',
                  ],
                  3 =>
                  [
                    'type' => 'uint256[]',
                    'name' => '',
                  ],
                  4 =>
                  [
                    'type' => 'bytes',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => '',
                  ],
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'onERC1155Received',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'operator',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                  3 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                  4 =>
                  [
                    'type' => 'bytes',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => '',
                  ],
                ],
              ],
              32 =>
              [
                'type' => 'function',
                'name' => 'postMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'sender',
                  ],
                  2 =>
                  [
                    'type' => 'bytes',
                    'name' => 'data',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'receiver',
                  ],
                ],
              ],
              33 =>
              [
                'type' => 'function',
                'name' => 'removeSchainContract',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              34 =>
              [
                'type' => 'function',
                'name' => 'renounceRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              35 =>
              [
                'type' => 'function',
                'name' => 'revokeRole',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'role',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              36 =>
              [
                'type' => 'function',
                'name' => 'schainLinks',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              37 =>
              [
                'type' => 'function',
                'name' => 'schainToERC1155',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              38 =>
              [
                'type' => 'function',
                'name' => 'supportsInterface',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes4',
                    'name' => 'interfaceId',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              39 =>
              [
                'type' => 'function',
                'name' => 'transferredAmount',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => '',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => '',
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => '',
                  ],
                ],
              ],
            ],
          ];
    }
}
