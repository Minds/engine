<?php

namespace Minds\Core\Blockchain\Polygon;

class PolygonContractAddressMainnet
{
    public function __construct()
    {
    }

    /**
     * Converted from JSON using https://dataconverter.curiousconcept.com/
     * @return array
     */
    public function getContracts(): array
    {
        return [
            'Main' => [
              'NetworkName' => 'Ethereum',
              'ChainId' => 1,
              'DaggerEndpoint' => 'wss://mainnet.dagger.matic.network',
              'WatcherAPI' => 'https://sentinel.matic.network/api/v2',
              'StakingAPI' => 'https://sentinel.matic.network/api/v2',
              'Explorer' => 'https://etherscan.io',
              'SubgraphUrl' => 'https://thegraph.com/hosted-service/subgraph/maticnetwork/mainnet-root-subgraphs',
              'SupportsEIP1559' => true,
              'Contracts' => [
                'BytesLib' => '0x1d21fACFC8CaD068eF0cbc87FdaCdFb20D7e2417',
                'Common' => '0x31851aAf1FA4cC6632f45570c2086aDcF8B7BD75',
                'ECVerify' => '0x71d91a8988D81617be53427126ee62471321b7DF',
                'Merkle' => '0x8b90C7633F1f751E19E76433990B1663c625B258',
                'MerklePatriciaProof' => '0x8E51a119E892D3fb324C0410F11f39F61dec9DC8',
                'PriorityQueue' => '0x61AdDcD534Bdc1721c91740Cf711dBEcE936053e',
                'RLPEncode' => '0x021c2Bf4d2941cE3D593e07317EC355937bae495',
                'RLPReader' => '0xD75f1d6A8A7Dc558A65c2f30eBF876DdbeE035a2',
                'SafeMath' => '0x96D358795782a73d90F2ed2d505aB235D197ca05',
                'Governance' => '0x98165b71cdDea047C0A49413350C40571195fd07',
                'GovernanceProxy' => '0x6e7a5820baD6cebA8Ef5ea69c0C92EbbDAc9CE48',
                'Registry' => '0x33a02E6cC863D393d6Bf231B697b82F6e499cA71',
                'RootChain' => '0x536c55cFe4892E581806e10b38dFE8083551bd03',
                'RootChainProxy' => '0x86E4Dc95c7FBdBf52e33D563BbDB00823894C287',
                'ValidatorShareFactory' => '0xc4FA447A0e77Eff9717b09C057B40570813bb642',
                'StakingInfo' => '0xa59C847Bd5aC0172Ff4FE912C5d29E5A71A7512B',
                'StakingNFT' => '0x47Cbe25BbDB40a774cC37E1dA92d10C2C7Ec897F',
                'StakeManager' => '0xd6f5c46d4e1a02f9d145cee41d2f8af30d8d2d76',
                'StakeManagerProxy' => '0x5e3Ef299fDDf15eAa0432E6e66473ace8c13D908',
                'SlashingManager' => '0x01F645DcD6C796F6BC6C982159B32fAaaebdC96A',
                'ValidatorShare' => '0x01d5dc56ad4206bb0c132d834644d57f51fed5ec',
                'StateSender' => '0x28e4F3a7f651294B9564800b2D01f35189A5bFbE',
                'DepositManager' => '0xd505C3822C787D51d5C2B1ae9aDB943B2304eB23',
                'DepositManagerProxy' => '0x401F6c983eA34274ec46f84D70b31C151321188b',
                'WithdrawManager' => '0x017C89Ca4Bda3D66cC65E3d20DD95432258201Ca',
                'WithdrawManagerProxy' => '0x2A88696e0fFA76bAA1338F2C74497cC013495922',
                'ExitNFT' => '0xDF74156420Bd57ab387B195ed81EcA36F9fABAca',
                'ERC20Predicate' => '0x158d5fa3ef8e4dda8a5367decf76b94e7effce95',
                'ERC721Predicate' => '0x54150f44c785d412ec262fe895cc3b689c72f49b',
                'EIP1559Burn' => '0x70bca57f4579f58670ab2d18ef16e02c17553c38',
                'Tokens' => [
                  'MaticToken' => '0x7D1AfA7B718fb893dB30A3aBc0Cfc608AaCfeBB0',
                  'TestToken' => '0x3db715989dA05C1D17441683B5b41d4510512722',
                  'RootERC721' => '0x96CDDF45C0Cd9a59876A2a29029d7c54f6e54AD3',
                  'MaticWeth' => '0xa45b966996374E9e65ab991C6FE4Bfce3a56DDe8',
                ],
              ],
              'POSContracts' => [
                'Merkle' => '0x195fe6EE6639665CCeB15BCCeB9980FC445DFa0B',
                'MerklePatriciaProof' => '0xA6FA4fB5f76172d178d61B04b0ecd319C5d1C0aa',
                'RLPReader' => '0xBEFe614A45A8300f2a4A00fb634b7137b6b1Bc47',
                'SafeERC20' => '0xeFfdCB49C2D0EF813764B709Ca3c6fe71f230E3e',
                'SafeMath' => '0x6EBEAC13f6403D19C95b6B75008B12fd21a93Aab',
                'RootChainManager' => '0x7cfa0f105a4922e89666d7d63689d9c9b1ea7a19',
                'RootChainManagerProxy' => '0xA0c68C638235ee32657e8f720a23ceC1bFc77C77',
                'ERC20Predicate' => '0x608669d4914eec1e20408bc4c9efff27bb8cbde5',
                'ERC20PredicateProxy' => '0x40ec5B33f54e0E8A33A975908C5BA1c14e5BbbDf',
                'ERC721Predicate' => '0xb272b6d99858b0efb079946942006727fe105201',
                'ERC721PredicateProxy' => '0xE6F45376f64e1F568BD1404C155e5fFD2F80F7AD',
                'ERC1155Predicate' => '0x62d7e87677ac7e3bd02c198e3fabeffdbc5eb2a3',
                'ERC1155PredicateProxy' => '0x0B9020d4E32990D67559b1317c7BF0C15D6EB88f',
                'MintableERC20Predicate' => '0xFdc26CDA2d2440d0E83CD1DeE8E8bE48405806DC',
                'MintableERC20PredicateProxy' => '0x9923263fA127b3d1484cFD649df8f1831c2A74e4',
                'MintableERC721Predicate' => '0x58adfa7960bf7cf39965b46d796fe66cd8f38283',
                'MintableERC721PredicateProxy' => '0x932532aA4c0174b8453839A6E44eE09Cc615F2b7',
                'MintableERC1155Predicate' => '0x62414d03084eeb269e18c970a21f45d2967f0170',
                'MintableERC1155PredicateProxy' => '0x2d641867411650cd05dB93B59964536b1ED5b1B7',
                'EtherPredicate' => '0x499a865ac595e6167482d2bd5a224876bab85ab4',
                'EtherPredicateProxy' => '0x8484Ef722627bf18ca5Ae6BcF031c23E6e922B30',
                'DummyStateSender' => '0x53E0bca35eC356BD5ddDFebbD1Fc0fD03FaBad39',
                'Tokens' => [
                  'DummyERC20' => '0xf2F3bD7Ca5746C5fac518f67D1BE87805a2Be82A',
                  'DummyERC721' => '0x71B821aa52a49F32EEd535fCA6Eb5aa130085978',
                  'DummyMintableERC721' => '0x578360AdF0BbB2F10ec9cEC7EF89Ef495511ED5f',
                  'DummyERC1155' => '0x556f501CF8a43216Df5bc9cC57Eb04D4FFAA9e6D',
                ],
              ],
              'FxPortalContracts' => [
                'FxRoot' => '0xfe5e5D361b2ad62c541bAb87C45a0B9B018389a2',
                'FxERC20RootTunnel' => '0xF1D80Ecb5de086b197EB2683513A3Da4061F0102',
                'FxERC721RootTunnel' => '0xca1f5ec720eCdA31bE3d80BD3ef4686cBb07eb4D',
                'FxERC1155RootTunnel' => '0x1E2baf7541C68FAfd0560FB87D2eAb0c4E51589d',
              ],
            ],
            'Matic' => [
              'NetworkName' => 'Polygon',
              'ChainId' => 137,
              'RPC' => 'https://polygon-rpc.com',
              'DaggerEndpoint' => 'wss://matic-mainnet.dagger.matic.network',
              'Explorer' => 'https://polygonscan.com',
              'NetworkAPI' => 'https://apis.matic.network/api/v1/matic',
              'SupportsEIP1559' => true,
              'Contracts' => [
                'ChildChain' => '0xD9c7C4ED4B66858301D0cb28Cc88bf655Fe34861',
                'EIP1559Burn' => '0x70bca57f4579f58670ab2d18ef16e02c17553c38',
                'Tokens' => [
                  'MaticWeth' => '0x8cc8538d60901d19692F5ba22684732Bc28F54A3',
                  'MaticToken' => '0x0000000000000000000000000000000000001010',
                  'TestToken' => '0x5E1DDF2e5a0eCDD923692d4b4429d8603825A8C6',
                  'RootERC721' => '0xa35363CFf92980F8268299D0132D5f45834A9527',
                  'WMATIC' => '0x0d500B1d8E8eF31E21C99d1Db9A6444d3ADf1270',
                ],
              ],
              'POSContracts' => [
                'ChildChainManager' => '0xa40fc0782bee28dd2cf8cb4ac2ecdb05c537f1b5',
                'ChildChainManagerProxy' => '0xA6FA4fB5f76172d178d61B04b0ecd319C5d1C0aa',
                'Tokens' => [
                  'DummyERC20' => '0xeFfdCB49C2D0EF813764B709Ca3c6fe71f230E3e',
                  'DummyERC721' => '0x6EBEAC13f6403D19C95b6B75008B12fd21a93Aab',
                  'DummyMintableERC721' => '0xD4888faB8bd39A663B63161F5eE1Eae31a25B653',
                  'DummyERC1155' => '0xA0c68C638235ee32657e8f720a23ceC1bFc77C77',
                  'MaticWETH' => '0x7ceB23fD6bC0adD59E62ac25578270cFf1b9f619',
                ],
              ],
              'FxPortalContracts' => [
                'FxChild' => '0x8397259c983751DAf40400790063935a11afa28a',
                'FxERC20ChildTunnel' => '0x0cC2CaeD31490B546c741BD93dbba8Ab387f7F2c',
                'FxERC721ChildTunnel' => '0x2b4732e448b3023131a7b25046b3A5EF50CfCf71',
                'FxERC1155ChildTunnel' => '0x80a708B92939B373e86eF8e8cfc9e05EfE2f5e49',
              ],
              'GenesisContracts' => [
                'BorValidatorSet' => '0x0000000000000000000000000000000000001000',
                'StateReceiver' => '0x0000000000000000000000000000000000001001',
              ],
            ],
            'Heimdall' => [
              'ChainId' => 'heimdall-137',
              'API' => 'https://heimdall-api.polygon.technology'
            ]
        ];
    }
}
