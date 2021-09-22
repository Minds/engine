<?php

namespace Minds\Core\Blockchain\SKALE;

class SKALEContractsSkaleNetwork
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
            'proxy_admin_address' => '0xd2aAa00000000000000000000000000000000000',
            'proxy_admin_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'previousOwner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'newOwner',
                    'type' => 'address',
                  ],
                ],
                'name' => 'OwnershipTransferred',
                'type' => 'event',
              ],
              1 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TransparentUpgradeableProxy',
                    'name' => 'proxy',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newAdmin',
                    'type' => 'address',
                  ],
                ],
                'name' => 'changeProxyAdmin',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              2 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TransparentUpgradeableProxy',
                    'name' => 'proxy',
                    'type' => 'address',
                  ],
                ],
                'name' => 'getProxyAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              3 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TransparentUpgradeableProxy',
                    'name' => 'proxy',
                    'type' => 'address',
                  ],
                ],
                'name' => 'getProxyImplementation',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              4 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'owner',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              5 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'renounceOwnership',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              6 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newOwner',
                    'type' => 'address',
                  ],
                ],
                'name' => 'transferOwnership',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TransparentUpgradeableProxy',
                    'name' => 'proxy',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'implementation',
                    'type' => 'address',
                  ],
                ],
                'name' => 'upgrade',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TransparentUpgradeableProxy',
                    'name' => 'proxy',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'implementation',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'upgradeAndCall',
                'outputs' =>
                [
                ],
                'stateMutability' => 'payable',
                'type' => 'function',
              ],
            ],
            'message_proxy_chain_address' => '0xd2AAa00100000000000000000000000000000000',
            'message_proxy_chain_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'oldValue',
                    'type' => 'uint256',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'newValue',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'GasLimitWasChanged',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'dstChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'uint256',
                    'name' => 'msgCounter',
                    'type' => 'uint256',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'srcContract',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'dstContract',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'OutgoingMessage',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'uint256',
                    'name' => 'msgCounter',
                    'type' => 'uint256',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bytes',
                    'name' => 'message',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'PostMessageError',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'CHAIN_CONNECTOR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'CONSTANT_SETTER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'EXTRA_CONTRACT_REGISTRAR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MESSAGES_LENGTH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'chainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'addConnectedChain',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'connectedChains',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'incomingMessageCounter',
                    'type' => 'uint256',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'outgoingMessageCounter',
                    'type' => 'uint256',
                  ],
                  2 =>
                  [
                    'internalType' => 'bool',
                    'name' => 'inited',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'gasLimit',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'fromSchainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'getIncomingMessagesCounter',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'targetSchainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'getOutgoingMessagesCounter',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract KeyStorage',
                    'name' => 'blsKeyStorage',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'newGasLimit',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'initializeMessageProxy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'isConnectedChain',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractAddress',
                    'type' => 'address',
                  ],
                ],
                'name' => 'isContractRegistered',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'keyStorage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract KeyStorage',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'fromChainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'startingCounter',
                    'type' => 'uint256',
                  ],
                  2 =>
                  [
                    'components' =>
                    [
                      0 =>
                      [
                        'internalType' => 'address',
                        'name' => 'sender',
                        'type' => 'address',
                      ],
                      1 =>
                      [
                        'internalType' => 'address',
                        'name' => 'destinationContract',
                        'type' => 'address',
                      ],
                      2 =>
                      [
                        'internalType' => 'bytes',
                        'name' => 'data',
                        'type' => 'bytes',
                      ],
                    ],
                    'internalType' => 'struct MessageProxy.Message[]',
                    'name' => 'messages',
                    'type' => 'tuple[]',
                  ],
                  3 =>
                  [
                    'components' =>
                    [
                      0 =>
                      [
                        'internalType' => 'uint256[2]',
                        'name' => 'blsSignature',
                        'type' => 'uint256[2]',
                      ],
                      1 =>
                      [
                        'internalType' => 'uint256',
                        'name' => 'hashA',
                        'type' => 'uint256',
                      ],
                      2 =>
                      [
                        'internalType' => 'uint256',
                        'name' => 'hashB',
                        'type' => 'uint256',
                      ],
                      3 =>
                      [
                        'internalType' => 'uint256',
                        'name' => 'counter',
                        'type' => 'uint256',
                      ],
                    ],
                    'internalType' => 'struct MessageProxy.Signature',
                    'name' => 'signature',
                    'type' => 'tuple',
                  ],
                ],
                'name' => 'postIncomingMessages',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'targetChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'targetContract',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postOutgoingMessage',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'chainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'extraContract',
                    'type' => 'address',
                  ],
                ],
                'name' => 'registerExtraContract',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'extraContract',
                    'type' => 'address',
                  ],
                ],
                'name' => 'registerExtraContractForAll',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'name' => 'registryContracts',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              32 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'chainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'removeConnectedChain',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              33 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'chainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'extraContract',
                    'type' => 'address',
                  ],
                ],
                'name' => 'removeExtraContract',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              34 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'extraContract',
                    'type' => 'address',
                  ],
                ],
                'name' => 'removeExtraContractForAll',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              35 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              36 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              37 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'schainHash',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              38 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'newGasLimit',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'setNewGasLimit',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              39 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              40 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'components' =>
                    [
                      0 =>
                      [
                        'internalType' => 'bytes32',
                        'name' => 'dstChain',
                        'type' => 'bytes32',
                      ],
                      1 =>
                      [
                        'internalType' => 'uint256',
                        'name' => 'msgCounter',
                        'type' => 'uint256',
                      ],
                      2 =>
                      [
                        'internalType' => 'address',
                        'name' => 'srcContract',
                        'type' => 'address',
                      ],
                      3 =>
                      [
                        'internalType' => 'address',
                        'name' => 'dstContract',
                        'type' => 'address',
                      ],
                      4 =>
                      [
                        'internalType' => 'bytes',
                        'name' => 'data',
                        'type' => 'bytes',
                      ],
                    ],
                    'internalType' => 'struct MessageProxyForSchain.OutgoingMessageData',
                    'name' => 'message',
                    'type' => 'tuple',
                  ],
                ],
                'name' => 'verifyOutgoingMessageData',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => 'isValidMessage',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
            ],
            'key_storage_address' => '0xd2aaa00200000000000000000000000000000000',
            'key_storage_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              3 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              4 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'FN_NUM_GET_CONFIG_VARIABLE_UINT256',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              5 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'FREE_MEM_PTR',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'getBlsCommonPublicKey',
                'outputs' =>
                [
                  0 =>
                  [
                    'components' =>
                    [
                      0 =>
                      [
                        'components' =>
                        [
                          0 =>
                          [
                            'internalType' => 'uint256',
                            'name' => 'a',
                            'type' => 'uint256',
                          ],
                          1 =>
                          [
                            'internalType' => 'uint256',
                            'name' => 'b',
                            'type' => 'uint256',
                          ],
                        ],
                        'internalType' => 'struct Fp2Operations.Fp2Point',
                        'name' => 'x',
                        'type' => 'tuple',
                      ],
                      1 =>
                      [
                        'components' =>
                        [
                          0 =>
                          [
                            'internalType' => 'uint256',
                            'name' => 'a',
                            'type' => 'uint256',
                          ],
                          1 =>
                          [
                            'internalType' => 'uint256',
                            'name' => 'b',
                            'type' => 'uint256',
                          ],
                        ],
                        'internalType' => 'struct Fp2Operations.Fp2Point',
                        'name' => 'y',
                        'type' => 'tuple',
                      ],
                    ],
                    'internalType' => 'struct G2Operations.G2Point',
                    'name' => '',
                    'type' => 'tuple',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
            ],
            'community_locker_address' => '0xD2aaa00300000000000000000000000000000000',
            'community_locker_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bytes32',
                    'name' => 'schainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'user',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ActivateUser',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bytes32',
                    'name' => 'schainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'user',
                    'type' => 'address',
                  ],
                ],
                'name' => 'LockUser',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'oldValue',
                    'type' => 'uint256',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'newValue',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'TimeLimitPerMessageWasChanged',
                'type' => 'event',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'CONSTANT_SETTER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_NAME',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'name' => 'activeUsers',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'receiver',
                    'type' => 'address',
                  ],
                ],
                'name' => 'checkAllowedToSendMessage',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'communityPool',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newTokenManagerLinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newCommunityPool',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'messageProxy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'fromChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'schainHash',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'newTimeLimitPerMessage',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'setTimeLimitPerMessage',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'timeLimitPerMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'tokenManagerLinker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
            ],
            'token_manager_linker_address' => '0xD2aAA00800000000000000000000000000000000',
            'token_manager_linker_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bool',
                    'name' => 'isAllowed',
                    'type' => 'bool',
                  ],
                ],
                'name' => 'InterchainConnectionAllowed',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              4 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              5 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_NAME',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'REGISTRAR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address[]',
                    'name' => 'tokenManagerAddresses',
                    'type' => 'address[]',
                  ],
                ],
                'name' => 'connectSchain',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'disconnectSchain',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'hasSchain',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => 'connected',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManager',
                    'name' => 'tokenManager',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasTokenManager',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxy',
                    'name' => 'newMessageProxyAddress',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'linker',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'interchainConnections',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'linkerAddress',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'messageProxy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxy',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'fromChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManager',
                    'name' => 'newTokenManager',
                    'type' => 'address',
                  ],
                ],
                'name' => 'registerTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManager',
                    'name' => 'tokenManagerAddress',
                    'type' => 'address',
                  ],
                ],
                'name' => 'removeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'tokenManagers',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManager',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
            ],
            'token_manager_eth_address' => '0xd2AaA00400000000000000000000000000000000',
            'token_manager_eth_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'oldValue',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'newValue',
                    'type' => 'address',
                  ],
                ],
                'name' => 'DepositBoxWasChanged',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              4 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              5 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_NAME',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'TOKEN_REGISTRAR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newTokenManager',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'automaticDeploy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'changeDepositBoxAddress',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'communityLocker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'depositBox',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'disableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'enableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'ethErc20',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract EthErc20',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'exitToMain',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'hasTokenManager',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newChainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                  5 =>
                  [
                    'internalType' => 'contract EthErc20',
                    'name' => 'ethErc20Address',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initializeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'messageProxy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'fromChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'removeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'schainHash',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              32 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract EthErc20',
                    'name' => 'newEthErc20Address',
                    'type' => 'address',
                  ],
                ],
                'name' => 'setEthErc20Address',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              33 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              34 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'tokenManagerLinker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              35 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'tokenManagers',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              36 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'targetSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferToSchain',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'token_manager_erc20_address' => '0xD2aAA00500000000000000000000000000000000',
            'token_manager_erc20_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'oldValue',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'newValue',
                    'type' => 'address',
                  ],
                ],
                'name' => 'DepositBoxWasChanged',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc20OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc20OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ERC20TokenAdded',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc20OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc20OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ERC20TokenCreated',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc20OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc20OnSchain',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'ERC20TokenReceived',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              6 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_NAME',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'TOKEN_REGISTRAR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'erc20OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract ERC20OnChain',
                    'name' => 'erc20OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addERC20TokenByOwner',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newTokenManager',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'automaticDeploy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'changeDepositBoxAddress',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'name' => 'clonesErc20',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract ERC20OnChain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'communityLocker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'depositBox',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'disableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'enableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'exitToMainERC20',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'hasTokenManager',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newChainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initializeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'messageProxy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'fromChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              32 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'removeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              33 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              34 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              35 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'schainHash',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              36 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              37 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'tokenManagerLinker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              38 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'tokenManagers',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              39 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract IERC20Upgradeable',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'name' => 'totalSupplyOnMainnet',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              40 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'targetSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferToSchainERC20',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'token_manager_erc721_address' => '0xD2aaa00600000000000000000000000000000000',
            'token_manager_erc721_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'oldValue',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'newValue',
                    'type' => 'address',
                  ],
                ],
                'name' => 'DepositBoxWasChanged',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc721OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc721OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ERC721TokenAdded',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc721OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc721OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ERC721TokenCreated',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc721OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc721OnSchain',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'ERC721TokenReceived',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              6 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_NAME',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'TOKEN_REGISTRAR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'erc721OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract ERC721OnChain',
                    'name' => 'erc721OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addERC721TokenByOwner',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newTokenManager',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'automaticDeploy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'changeDepositBoxAddress',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'name' => 'clonesErc721',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract ERC721OnChain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'communityLocker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'depositBox',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'disableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'enableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'exitToMainERC721',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'hasTokenManager',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newChainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initializeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'messageProxy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'fromChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              32 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'removeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              33 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              34 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              35 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'schainHash',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              36 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              37 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'tokenManagerLinker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              38 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'tokenManagers',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              39 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'targetSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferToSchainERC721',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'token_manager_erc1155_address' => '0xD2aaA00900000000000000000000000000000000',
            'token_manager_erc1155_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'oldValue',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => false,
                    'internalType' => 'address',
                    'name' => 'newValue',
                    'type' => 'address',
                  ],
                ],
                'name' => 'DepositBoxWasChanged',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc1155OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ERC1155TokenAdded',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc1155OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'ERC1155TokenCreated',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'erc1155OnSchain',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  3 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256[]',
                    'name' => 'amounts',
                    'type' => 'uint256[]',
                  ],
                ],
                'name' => 'ERC1155TokenReceived',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              6 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_HASH',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MAINNET_NAME',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'TOKEN_REGISTRAR_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract ERC1155OnChain',
                    'name' => 'erc1155OnSchain',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addERC1155TokenByOwner',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newTokenManager',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'automaticDeploy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'changeDepositBoxAddress',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'name' => 'clonesErc1155',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract ERC1155OnChain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'communityLocker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'depositBox',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'disableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'enableAutomaticDeploy',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'exitToMainERC1155',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'amounts',
                    'type' => 'uint256[]',
                  ],
                ],
                'name' => 'exitToMainERC1155Batch',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'hasTokenManager',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newChainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'newSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => 'newMessageProxy',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => 'newIMALinker',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'contract CommunityLocker',
                    'name' => 'newCommunityLocker',
                    'type' => 'address',
                  ],
                  4 =>
                  [
                    'internalType' => 'address',
                    'name' => 'newDepositBox',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initializeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'messageProxy',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract MessageProxyForSchain',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              32 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'fromChainHash',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'postMessage',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              33 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'schainName',
                    'type' => 'string',
                  ],
                ],
                'name' => 'removeTokenManager',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              34 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              35 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              36 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'schainHash',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              37 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              38 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'tokenManagerLinker',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'contract TokenManagerLinker',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              39 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'tokenManagers',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              40 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'targetSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                  4 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferToSchainERC1155',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              41 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'targetSchainName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'contractOnMainnet',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  4 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'amounts',
                    'type' => 'uint256[]',
                  ],
                ],
                'name' => 'transferToSchainERC1155Batch',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'eth_erc20_address' => '0xD2Aaa00700000000000000000000000000000000',
            'eth_erc20_abi' =>
            [
              0 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'Approval',
                'type' => 'event',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'Transfer',
                'type' => 'event',
              ],
              5 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'BURNER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MINTER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'allowance',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'approve',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'balanceOf',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'burn',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'burnFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'decimals',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint8',
                    'name' => '',
                    'type' => 'uint8',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'subtractedValue',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'decreaseAllowance',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'forceBurn',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'addedValue',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'increaseAllowance',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'tokenManagerEthAddress',
                    'type' => 'address',
                  ],
                ],
                'name' => 'initialize',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'mint',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'name',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'symbol',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'totalSupply',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'recipient',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transfer',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'recipient',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferFrom',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'ERC20OnChain_abi' =>
            [
              0 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'contractName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'string',
                    'name' => 'contractSymbol',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'constructor',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'Approval',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'Transfer',
                'type' => 'event',
              ],
              6 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MINTER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'allowance',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'approve',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'balanceOf',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'burn',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'burnFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'decimals',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint8',
                    'name' => '',
                    'type' => 'uint8',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'subtractedValue',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'decreaseAllowance',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'spender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'addedValue',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'increaseAllowance',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'mint',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'name',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'symbol',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'totalSupply',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'recipient',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transfer',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'recipient',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferFrom',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'ERC721OnChain_abi' =>
            [
              0 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'contractName',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'string',
                    'name' => 'contractSymbol',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'constructor',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'approved',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'Approval',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bool',
                    'name' => 'approved',
                    'type' => 'bool',
                  ],
                ],
                'name' => 'ApprovalForAll',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              6 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'Transfer',
                'type' => 'event',
              ],
              7 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MINTER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'approve',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                ],
                'name' => 'balanceOf',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'burn',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getApproved',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'owner',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                ],
                'name' => 'isApprovedForAll',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'mint',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'name',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'ownerOf',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'safeTransferFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                  3 =>
                  [
                    'internalType' => 'bytes',
                    'name' => '_data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'safeTransferFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'bool',
                    'name' => 'approved',
                    'type' => 'bool',
                  ],
                ],
                'name' => 'setApprovalForAll',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                  1 =>
                  [
                    'internalType' => 'string',
                    'name' => 'tokenUri',
                    'type' => 'string',
                  ],
                ],
                'name' => 'setTokenURI',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              29 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'symbol',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              30 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'tokenURI',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              31 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'tokenId',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'transferFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'ERC1155OnChain_abi' =>
            [
              0 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => 'uri',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'constructor',
              ],
              1 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => false,
                    'internalType' => 'bool',
                    'name' => 'approved',
                    'type' => 'bool',
                  ],
                ],
                'name' => 'ApprovalForAll',
                'type' => 'event',
              ],
              2 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'previousAdminRole',
                    'type' => 'bytes32',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'newAdminRole',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'RoleAdminChanged',
                'type' => 'event',
              ],
              3 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleGranted',
                'type' => 'event',
              ],
              4 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'sender',
                    'type' => 'address',
                  ],
                ],
                'name' => 'RoleRevoked',
                'type' => 'event',
              ],
              5 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  4 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256[]',
                    'name' => 'values',
                    'type' => 'uint256[]',
                  ],
                ],
                'name' => 'TransferBatch',
                'type' => 'event',
              ],
              6 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'indexed' => true,
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  3 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                  4 =>
                  [
                    'indexed' => false,
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'TransferSingle',
                'type' => 'event',
              ],
              7 =>
              [
                'anonymous' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'indexed' => false,
                    'internalType' => 'string',
                    'name' => 'value',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'indexed' => true,
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'URI',
                'type' => 'event',
              ],
              8 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'DEFAULT_ADMIN_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'inputs' =>
                [
                ],
                'name' => 'MINTER_ROLE',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              10 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'balanceOf',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              11 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address[]',
                    'name' => 'accounts',
                    'type' => 'address[]',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                ],
                'name' => 'balanceOfBatch',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => '',
                    'type' => 'uint256[]',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'value',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'burn',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'values',
                    'type' => 'uint256[]',
                  ],
                ],
                'name' => 'burnBatch',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleAdmin',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => '',
                    'type' => 'bytes32',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'index',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'getRoleMember',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => '',
                    'type' => 'address',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              16 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                ],
                'name' => 'getRoleMemberCount',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'grantRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              18 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'hasRole',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                ],
                'name' => 'isApprovedForAll',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                  3 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'mint',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'amounts',
                    'type' => 'uint256[]',
                  ],
                  3 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'mintBatch',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              22 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'renounceRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              23 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes32',
                    'name' => 'role',
                    'type' => 'bytes32',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'revokeRole',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              24 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'ids',
                    'type' => 'uint256[]',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256[]',
                    'name' => 'amounts',
                    'type' => 'uint256[]',
                  ],
                  4 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'safeBatchTransferFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              25 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'from',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'address',
                    'name' => 'to',
                    'type' => 'address',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'id',
                    'type' => 'uint256',
                  ],
                  3 =>
                  [
                    'internalType' => 'uint256',
                    'name' => 'amount',
                    'type' => 'uint256',
                  ],
                  4 =>
                  [
                    'internalType' => 'bytes',
                    'name' => 'data',
                    'type' => 'bytes',
                  ],
                ],
                'name' => 'safeTransferFrom',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              26 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'operator',
                    'type' => 'address',
                  ],
                  1 =>
                  [
                    'internalType' => 'bool',
                    'name' => 'approved',
                    'type' => 'bool',
                  ],
                ],
                'name' => 'setApprovalForAll',
                'outputs' =>
                [
                ],
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              27 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bytes4',
                    'name' => 'interfaceId',
                    'type' => 'bytes4',
                  ],
                ],
                'name' => 'supportsInterface',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              28 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '',
                    'type' => 'uint256',
                  ],
                ],
                'name' => 'uri',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '',
                    'type' => 'string',
                  ],
                ],
                'stateMutability' => 'view',
                'type' => 'function',
              ],
            ],
        ];
    }
}
