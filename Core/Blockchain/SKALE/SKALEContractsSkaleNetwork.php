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
            'community_locker_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ActivateUser',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'user',
                    'indexed' => false,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'LockUser',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bytes32',
                    'name' => 'schainHash',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'user',
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
                'type' => 'event',
                'anonymous' => false,
                'name' => 'TimeLimitPerMessageWasChanged',
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
              6 =>
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
              7 =>
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
              8 =>
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
              9 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_NAME',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              10 =>
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
              11 =>
              [
                'type' => 'function',
                'name' => 'checkAllowedToSendMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'receiver',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              12 =>
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
              13 =>
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
              14 =>
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
              15 =>
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
              16 =>
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
              17 =>
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
                    'type' => 'string',
                    'name' => 'newSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newTokenManagerLinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityPool',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              19 =>
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
              20 =>
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
                    'name' => 'fromChainHash',
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
              21 =>
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
              22 =>
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
              23 =>
              [
                'type' => 'function',
                'name' => 'schainHash',
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
              24 =>
              [
                'type' => 'function',
                'name' => 'setTimeLimitPerMessage',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'newTimeLimitPerMessage',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
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
              26 =>
              [
                'type' => 'function',
                'name' => 'timeLimitPerMessage',
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
                'name' => 'tokenManagerLinker',
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
            ],
            'community_locker_address' => '0xD2aaa00300000000000000000000000000000000',
            'eth_erc20_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'Approval',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'owner',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'spender',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'value',
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
                'type' => 'event',
                'anonymous' => false,
                'name' => 'Transfer',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'from',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'to',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'value',
                    'indexed' => false,
                  ],
                ],
              ],
              5 =>
              [
                'type' => 'function',
                'name' => 'BURNER_ROLE',
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
              7 =>
              [
                'type' => 'function',
                'name' => 'MINTER_ROLE',
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
                'name' => 'allowance',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'owner',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'spender',
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
              9 =>
              [
                'type' => 'function',
                'name' => 'approve',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'spender',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
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
              10 =>
              [
                'type' => 'function',
                'name' => 'balanceOf',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
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
                'name' => 'burn',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
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
                'name' => 'burnFrom',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
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
              13 =>
              [
                'type' => 'function',
                'name' => 'decimals',
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
                    'type' => 'uint8',
                    'name' => '',
                  ],
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'decreaseAllowance',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'spender',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'subtractedValue',
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
                'name' => 'forceBurn',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
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
                'name' => 'increaseAllowance',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'spender',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'addedValue',
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
                    'name' => 'tokenManagerEthAddress',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'mint',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'account',
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
              24 =>
              [
                'type' => 'function',
                'name' => 'name',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              25 =>
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
              26 =>
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
              27 =>
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
              28 =>
              [
                'type' => 'function',
                'name' => 'symbol',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              29 =>
              [
                'type' => 'function',
                'name' => 'totalSupply',
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
              30 =>
              [
                'type' => 'function',
                'name' => 'transfer',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'recipient',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
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
                'name' => 'transferFrom',
                'constant' => false,
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
                    'type' => 'address',
                    'name' => 'recipient',
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
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
            'eth_erc20_address' => '0xD2Aaa00700000000000000000000000000000000',
            'key_storage_abi' =>
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
                'name' => 'FN_NUM_GET_CONFIG_VARIABLE_UINT256',
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
              5 =>
              [
                'type' => 'function',
                'name' => 'FREE_MEM_PTR',
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
              6 =>
              [
                'type' => 'function',
                'name' => 'getBlsCommonPublicKey',
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
                    'type' => 'tuple',
                    'components' =>
                    [
                      0 =>
                      [
                        'type' => 'tuple',
                        'name' => 'x',
                        'components' =>
                        [
                          0 =>
                          [
                            'type' => 'uint256',
                            'name' => 'a',
                          ],
                          1 =>
                          [
                            'type' => 'uint256',
                            'name' => 'b',
                          ],
                        ],
                      ],
                      1 =>
                      [
                        'type' => 'tuple',
                        'name' => 'y',
                        'components' =>
                        [
                          0 =>
                          [
                            'type' => 'uint256',
                            'name' => 'a',
                          ],
                          1 =>
                          [
                            'type' => 'uint256',
                            'name' => 'b',
                          ],
                        ],
                      ],
                    ],
                    'name' => '',
                  ],
                ],
              ],
              7 =>
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
              8 =>
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
              9 =>
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
              10 =>
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
              11 =>
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
              12 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
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
              14 =>
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
              15 =>
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
            'key_storage_address' => '0xd2aaa00200000000000000000000000000000000',
            'message_proxy_chain_abi' =>
            [
              0 =>
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
              1 =>
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
              2 =>
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
              3 =>
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
              4 =>
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
              5 =>
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
              6 =>
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
              7 =>
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
              8 =>
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
              9 =>
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
              10 =>
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
              11 =>
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
              12 =>
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
                    'name' => 'chainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
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
              14 =>
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
              15 =>
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
              16 =>
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
              17 =>
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
              18 =>
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
              19 =>
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
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'blsKeyStorage',
                  ],
                  1 =>
                  [
                    'type' => 'string',
                    'name' => 'schainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
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
              24 =>
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
              25 =>
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
              26 =>
              [
                'type' => 'function',
                'name' => 'keyStorage',
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
                'name' => 'postIncomingMessages',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'fromChainName',
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
                    'name' => 'signature',
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
              28 =>
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
              29 =>
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
                    'name' => 'chainName',
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
              30 =>
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
              31 =>
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
              32 =>
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
                    'name' => 'chainName',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              33 =>
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
                    'name' => 'chainName',
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
              34 =>
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
              35 =>
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
              36 =>
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
              37 =>
              [
                'type' => 'function',
                'name' => 'schainHash',
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
              38 =>
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
              39 =>
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
              40 =>
              [
                'type' => 'function',
                'name' => 'verifyOutgoingMessageData',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'tuple',
                    'name' => 'message',
                    'components' =>
                    [
                      0 =>
                      [
                        'type' => 'bytes32',
                        'name' => 'dstChain',
                      ],
                      1 =>
                      [
                        'type' => 'uint256',
                        'name' => 'msgCounter',
                      ],
                      2 =>
                      [
                        'type' => 'address',
                        'name' => 'srcContract',
                      ],
                      3 =>
                      [
                        'type' => 'address',
                        'name' => 'dstContract',
                      ],
                      4 =>
                      [
                        'type' => 'bytes',
                        'name' => 'data',
                      ],
                    ],
                  ],
                ],
                'outputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => 'isValidMessage',
                  ],
                ],
              ],
            ],
            'message_proxy_chain_address' => '0xd2AAa00100000000000000000000000000000000',
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
            'proxy_admin_address' => '0xd2aAa00000000000000000000000000000000000',
            'token_manager_erc1155_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'DepositBoxWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC1155TokenAdded',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnSchain',
                    'indexed' => true,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC1155TokenCreated',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnSchain',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC1155TokenReceived',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnSchain',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'ids',
                    'indexed' => false,
                  ],
                  3 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'amounts',
                    'indexed' => false,
                  ],
                ],
              ],
              4 =>
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
              5 =>
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
              6 =>
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
              7 =>
              [
                'type' => 'function',
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
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
              9 =>
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
              10 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_NAME',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'TOKEN_REGISTRAR_ROLE',
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
                'name' => 'addERC1155TokenByOwner',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnMainnet',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc1155OnSchain',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'addTokenManager',
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
                    'name' => 'newTokenManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'addedClones',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'automaticDeploy',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'changeDepositBoxAddress',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'clonesErc1155',
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
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'communityLocker',
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
              19 =>
              [
                'type' => 'function',
                'name' => 'depositBox',
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
              20 =>
              [
                'type' => 'function',
                'name' => 'disableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'enableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'exitToMainERC1155',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'id',
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
              23 =>
              [
                'type' => 'function',
                'name' => 'exitToMainERC1155Batch',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                  ],
                  1 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'ids',
                  ],
                  2 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'amounts',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
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
              25 =>
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
              26 =>
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
              27 =>
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
              28 =>
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
              29 =>
              [
                'type' => 'function',
                'name' => 'hasTokenManager',
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
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newChainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              31 =>
              [
                'type' => 'function',
                'name' => 'initializeTokenManager',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              32 =>
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
              33 =>
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
                    'name' => 'fromChainHash',
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
              34 =>
              [
                'type' => 'function',
                'name' => 'removeTokenManager',
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
              35 =>
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
              36 =>
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
              37 =>
              [
                'type' => 'function',
                'name' => 'schainHash',
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
                'name' => 'tokenManagerLinker',
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
              40 =>
              [
                'type' => 'function',
                'name' => 'tokenManagers',
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
              41 =>
              [
                'type' => 'function',
                'name' => 'transferToSchainERC1155',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'targetSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'id',
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
              42 =>
              [
                'type' => 'function',
                'name' => 'transferToSchainERC1155Batch',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'targetSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'ids',
                  ],
                  3 =>
                  [
                    'type' => 'uint256[]',
                    'name' => 'amounts',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
            ],
            'token_manager_erc1155_address' => '0xD2aaA00900000000000000000000000000000000',
            'token_manager_erc20_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'DepositBoxWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC20TokenAdded',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnSchain',
                    'indexed' => true,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC20TokenCreated',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnSchain',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC20TokenReceived',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnSchain',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                    'indexed' => false,
                  ],
                ],
              ],
              4 =>
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
              5 =>
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
              6 =>
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
              7 =>
              [
                'type' => 'function',
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
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
              9 =>
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
              10 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_NAME',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'TOKEN_REGISTRAR_ROLE',
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
                'name' => 'addERC20TokenByOwner',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnMainnet',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc20OnSchain',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'addTokenManager',
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
                    'name' => 'newTokenManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'addedClones',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'automaticDeploy',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'changeDepositBoxAddress',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'clonesErc20',
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
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'communityLocker',
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
              19 =>
              [
                'type' => 'function',
                'name' => 'depositBox',
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
              20 =>
              [
                'type' => 'function',
                'name' => 'disableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'enableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'exitToMainERC20',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
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
              23 =>
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
              24 =>
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
              25 =>
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
              26 =>
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
              27 =>
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
              28 =>
              [
                'type' => 'function',
                'name' => 'hasTokenManager',
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
              29 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newChainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'initializeTokenManager',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              31 =>
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
                    'name' => 'fromChainHash',
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
              33 =>
              [
                'type' => 'function',
                'name' => 'removeTokenManager',
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
                'name' => 'schainHash',
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
              37 =>
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
              38 =>
              [
                'type' => 'function',
                'name' => 'tokenManagerLinker',
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
              39 =>
              [
                'type' => 'function',
                'name' => 'tokenManagers',
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
              40 =>
              [
                'type' => 'function',
                'name' => 'totalSupplyOnMainnet',
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
              41 =>
              [
                'type' => 'function',
                'name' => 'transferToSchainERC20',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'targetSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
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
            ],
            'token_manager_erc20_address' => '0xD2aAA00500000000000000000000000000000000',
            'token_manager_erc721_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'DepositBoxWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newValue',
                    'indexed' => false,
                  ],
                ],
              ],
              1 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC721TokenAdded',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnSchain',
                    'indexed' => true,
                  ],
                ],
              ],
              2 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC721TokenCreated',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnSchain',
                    'indexed' => true,
                  ],
                ],
              ],
              3 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'ERC721TokenReceived',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                    'indexed' => true,
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnSchain',
                    'indexed' => true,
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'tokenId',
                    'indexed' => false,
                  ],
                ],
              ],
              4 =>
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
              5 =>
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
              6 =>
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
              7 =>
              [
                'type' => 'function',
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
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
              9 =>
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
              10 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_NAME',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'TOKEN_REGISTRAR_ROLE',
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
                'name' => 'addERC721TokenByOwner',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnMainnet',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'erc721OnSchain',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              13 =>
              [
                'type' => 'function',
                'name' => 'addTokenManager',
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
                    'name' => 'newTokenManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              14 =>
              [
                'type' => 'function',
                'name' => 'addedClones',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'automaticDeploy',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'changeDepositBoxAddress',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              17 =>
              [
                'type' => 'function',
                'name' => 'clonesErc721',
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
                    'type' => 'address',
                    'name' => '',
                  ],
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'communityLocker',
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
              19 =>
              [
                'type' => 'function',
                'name' => 'depositBox',
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
              20 =>
              [
                'type' => 'function',
                'name' => 'disableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              21 =>
              [
                'type' => 'function',
                'name' => 'enableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              22 =>
              [
                'type' => 'function',
                'name' => 'exitToMainERC721',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                  ],
                  1 =>
                  [
                    'type' => 'uint256',
                    'name' => 'tokenId',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
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
              24 =>
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
              25 =>
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
              26 =>
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
              27 =>
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
              28 =>
              [
                'type' => 'function',
                'name' => 'hasTokenManager',
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
              29 =>
              [
                'type' => 'function',
                'name' => 'initialize',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newChainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              30 =>
              [
                'type' => 'function',
                'name' => 'initializeTokenManager',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              31 =>
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
                    'name' => 'fromChainHash',
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
              33 =>
              [
                'type' => 'function',
                'name' => 'removeTokenManager',
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
                'name' => 'schainHash',
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
              37 =>
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
              38 =>
              [
                'type' => 'function',
                'name' => 'tokenManagerLinker',
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
              39 =>
              [
                'type' => 'function',
                'name' => 'tokenManagers',
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
              40 =>
              [
                'type' => 'function',
                'name' => 'transferToSchainERC721',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'targetSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'contractOnMainnet',
                  ],
                  2 =>
                  [
                    'type' => 'uint256',
                    'name' => 'tokenId',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
            ],
            'token_manager_erc721_address' => '0xD2aaa00600000000000000000000000000000000',
            'token_manager_eth_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'DepositBoxWasChanged',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'oldValue',
                    'indexed' => false,
                  ],
                  1 =>
                  [
                    'type' => 'address',
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
                'name' => 'AUTOMATIC_DEPLOY_ROLE',
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
              7 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_NAME',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              8 =>
              [
                'type' => 'function',
                'name' => 'TOKEN_REGISTRAR_ROLE',
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
                'name' => 'addTokenManager',
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
                    'name' => 'newTokenManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              10 =>
              [
                'type' => 'function',
                'name' => 'automaticDeploy',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              11 =>
              [
                'type' => 'function',
                'name' => 'changeDepositBoxAddress',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              12 =>
              [
                'type' => 'function',
                'name' => 'communityLocker',
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
              13 =>
              [
                'type' => 'function',
                'name' => 'depositBox',
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
              14 =>
              [
                'type' => 'function',
                'name' => 'disableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              15 =>
              [
                'type' => 'function',
                'name' => 'enableAutomaticDeploy',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                ],
                'outputs' =>
                [
                ],
              ],
              16 =>
              [
                'type' => 'function',
                'name' => 'ethErc20',
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
              17 =>
              [
                'type' => 'function',
                'name' => 'exitToMain',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
                    'name' => 'amount',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              18 =>
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
              19 =>
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
              20 =>
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
              21 =>
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
              22 =>
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
              23 =>
              [
                'type' => 'function',
                'name' => 'hasTokenManager',
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
                    'type' => 'string',
                    'name' => 'newChainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                  5 =>
                  [
                    'type' => 'address',
                    'name' => 'ethErc20Address',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              25 =>
              [
                'type' => 'function',
                'name' => 'initializeTokenManager',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'newSchainName',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'newMessageProxy',
                  ],
                  2 =>
                  [
                    'type' => 'address',
                    'name' => 'newIMALinker',
                  ],
                  3 =>
                  [
                    'type' => 'address',
                    'name' => 'newCommunityLocker',
                  ],
                  4 =>
                  [
                    'type' => 'address',
                    'name' => 'newDepositBox',
                  ],
                ],
                'outputs' =>
                [
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
                    'name' => 'fromChainHash',
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
                'name' => 'removeTokenManager',
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
                'name' => 'schainHash',
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
              32 =>
              [
                'type' => 'function',
                'name' => 'setEthErc20Address',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newEthErc20Address',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              33 =>
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
              34 =>
              [
                'type' => 'function',
                'name' => 'tokenManagerLinker',
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
              35 =>
              [
                'type' => 'function',
                'name' => 'tokenManagers',
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
              36 =>
              [
                'type' => 'function',
                'name' => 'transferToSchain',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'string',
                    'name' => 'targetSchainName',
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
            'token_manager_eth_address' => '0xd2AaA00400000000000000000000000000000000',
            'token_manager_linker_abi' =>
            [
              0 =>
              [
                'type' => 'event',
                'anonymous' => false,
                'name' => 'InterchainConnectionAllowed',
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'bool',
                    'name' => 'isAllowed',
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
              5 =>
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
              6 =>
              [
                'type' => 'function',
                'name' => 'MAINNET_NAME',
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
                    'type' => 'string',
                    'name' => '',
                  ],
                ],
              ],
              7 =>
              [
                'type' => 'function',
                'name' => 'REGISTRAR_ROLE',
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
                    'name' => 'tokenManagerAddresses',
                  ],
                ],
                'outputs' =>
                [
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
              15 =>
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
              16 =>
              [
                'type' => 'function',
                'name' => 'hasTokenManager',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'tokenManager',
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
                    'name' => 'newMessageProxyAddress',
                  ],
                  1 =>
                  [
                    'type' => 'address',
                    'name' => 'linker',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              18 =>
              [
                'type' => 'function',
                'name' => 'interchainConnections',
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
                    'type' => 'bool',
                    'name' => '',
                  ],
                ],
              ],
              19 =>
              [
                'type' => 'function',
                'name' => 'linkerAddress',
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
              20 =>
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
              21 =>
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
                    'name' => 'fromChainHash',
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
              22 =>
              [
                'type' => 'function',
                'name' => 'registerTokenManager',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'newTokenManager',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              23 =>
              [
                'type' => 'function',
                'name' => 'removeTokenManager',
                'constant' => false,
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'address',
                    'name' => 'tokenManagerAddress',
                  ],
                ],
                'outputs' =>
                [
                ],
              ],
              24 =>
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
              25 =>
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
              26 =>
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
              27 =>
              [
                'type' => 'function',
                'name' => 'tokenManagers',
                'constant' => true,
                'stateMutability' => 'view',
                'payable' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'type' => 'uint256',
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
            ],
            'token_manager_linker_address' => '0xD2aAA00800000000000000000000000000000000',
        ];
    }
}
