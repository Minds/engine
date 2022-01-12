<?php

namespace Minds\Core\Blockchain\SKALE;

class SkMINDS
{
    public function __construct()
    {
    }

    /**
     * Converted from JSON using https://dataconverter.curiousconcept.com/
     * TODO: SWAPOUT WITH MAINNET CONTRACT OR MAKE SWITCH ON DEV MODE.
     * @return array
     */
    public function getABI(): array
    {
        return [
            'contractName' => 'MindsToken',
            'abi' =>
            [
              0 =>
              [
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'string',
                    'name' => '_name',
                    'type' => 'string',
                  ],
                  1 =>
                  [
                    'internalType' => 'string',
                    'name' => '_symbol',
                    'type' => 'string',
                  ],
                  2 =>
                  [
                    'internalType' => 'uint256',
                    'name' => '_decimals',
                    'type' => 'uint256',
                  ],
                ],
                'payable' => false,
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
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'MinterAdded',
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
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'MinterRemoved',
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
                'constant' => false,
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'addMinter',
                'outputs' =>
                [
                ],
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              6 =>
              [
                'constant' => true,
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
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              7 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              8 =>
              [
                'constant' => true,
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
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              9 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              10 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              11 =>
              [
                'constant' => true,
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
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              12 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              13 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              14 =>
              [
                'constant' => true,
                'inputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'address',
                    'name' => 'account',
                    'type' => 'address',
                  ],
                ],
                'name' => 'isMinter',
                'outputs' =>
                [
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              15 =>
              [
                'constant' => false,
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
                  0 =>
                  [
                    'internalType' => 'bool',
                    'name' => '',
                    'type' => 'bool',
                  ],
                ],
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              16 =>
              [
                'constant' => true,
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
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              17 =>
              [
                'constant' => false,
                'inputs' =>
                [
                ],
                'name' => 'renounceMinter',
                'outputs' =>
                [
                ],
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              18 =>
              [
                'constant' => true,
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
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              19 =>
              [
                'constant' => true,
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
                'payable' => false,
                'stateMutability' => 'view',
                'type' => 'function',
              ],
              20 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
              21 =>
              [
                'constant' => false,
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
                'payable' => false,
                'stateMutability' => 'nonpayable',
                'type' => 'function',
              ],
            ],
            'metadata' => '{"compiler":{"version":"0.5.17+commit.d19bba13"},"language":"Solidity","output":{"abi":[{"inputs":[{"internalType":"string","name":"_name","type":"string"},{"internalType":"string","name":"_symbol","type":"string"},{"internalType":"uint256","name":"_decimals","type":"uint256"}],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"owner","type":"address"},{"indexed":true,"internalType":"address","name":"spender","type":"address"},{"indexed":false,"internalType":"uint256","name":"value","type":"uint256"}],"name":"Approval","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"account","type":"address"}],"name":"MinterAdded","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"account","type":"address"}],"name":"MinterRemoved","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"constant":false,"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"addMinter","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"internalType":"address","name":"owner","type":"address"},{"internalType":"address","name":"spender","type":"address"}],"name":"allowance","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"approve","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"burn","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"account","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"burnFrom","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"internalType":"uint8","name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"subtractedValue","type":"uint256"}],"name":"decreaseAllowance","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"addedValue","type":"uint256"}],"name":"increaseAllowance","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"isMinter","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"account","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"mint","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"name","outputs":[{"internalType":"string","name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[],"name":"renounceMinter","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"internalType":"string","name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"recipient","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"transfer","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"sender","type":"address"},{"internalType":"address","name":"recipient","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"transferFrom","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"}],"devdoc":{"methods":{"allowance(address,address)":{"details":"See {IERC20-allowance}."},"approve(address,uint256)":{"details":"See {IERC20-approve}.     * Requirements:     * - `spender` cannot be the zero address."},"balanceOf(address)":{"details":"See {IERC20-balanceOf}."},"burn(uint256)":{"details":"Destroys `amount` tokens from the caller.     * See {ERC20-_burn}."},"burnFrom(address,uint256)":{"details":"See {ERC20-_burnFrom}."},"decimals()":{"details":"Returns the number of decimals used to get its user representation. For example, if `decimals` equals `2`, a balance of `505` tokens should be displayed to a user as `5,05` (`505 / 10 ** 2`).     * Tokens usually opt for a value of 18, imitating the relationship between Ether and Wei.     * NOTE: This information is only used for _display_ purposes: it in no way affects any of the arithmetic of the contract, including {IERC20-balanceOf} and {IERC20-transfer}."},"decreaseAllowance(address,uint256)":{"details":"Atomically decreases the allowance granted to `spender` by the caller.     * This is an alternative to {approve} that can be used as a mitigation for problems described in {IERC20-approve}.     * Emits an {Approval} event indicating the updated allowance.     * Requirements:     * - `spender` cannot be the zero address. - `spender` must have allowance for the caller of at least `subtractedValue`."},"increaseAllowance(address,uint256)":{"details":"Atomically increases the allowance granted to `spender` by the caller.     * This is an alternative to {approve} that can be used as a mitigation for problems described in {IERC20-approve}.     * Emits an {Approval} event indicating the updated allowance.     * Requirements:     * - `spender` cannot be the zero address."},"mint(address,uint256)":{"details":"See {ERC20-_mint}.     * Requirements:     * - the caller must have the {MinterRole}."},"name()":{"details":"Returns the name of the token."},"symbol()":{"details":"Returns the symbol of the token, usually a shorter version of the name."},"totalSupply()":{"details":"See {IERC20-totalSupply}."},"transfer(address,uint256)":{"details":"See {IERC20-transfer}.     * Requirements:     * - `recipient` cannot be the zero address. - the caller must have a balance of at least `amount`."},"transferFrom(address,address,uint256)":{"details":"See {IERC20-transferFrom}.     * Emits an {Approval} event indicating the updated allowance. This is not required by the EIP. See the note at the beginning of {ERC20};     * Requirements: - `sender` and `recipient` cannot be the zero address. - `sender` must have a balance of at least `amount`. - the caller must have allowance for `sender`\'s tokens of at least `amount`."}}},"userdoc":{"methods":{}}},"settings":{"compilationTarget":{"project:/src/contracts/MindsToken.sol":"MindsToken"},"evmVersion":"istanbul","libraries":{},"optimizer":{"enabled":false,"runs":200},"remappings":[]},"sources":{"@openzeppelin/contracts/GSN/Context.sol":{"keccak256":"0x90a3995645af7562d84b9d69363ffa5ae7217714ab61e951bf7bc450f40e4061","urls":["bzz-raw://216ef9d6b614db4eb46970b4e84903f2534a45572dd30a79f0041f1a5830f436","dweb:/ipfs/QmNPrJ4MWKUAWzKXpUqeyKRUfosaoANZAqXgvepdrCwZAG"]},"@openzeppelin/contracts/access/Roles.sol":{"keccak256":"0xb002c378d7b82a101bd659c341518953ca0919d342c0a400196982c0e7e7bcdb","urls":["bzz-raw://00a788c4631466c220b385bdd100c571d24b2deccd657615cfbcef6cadf669a4","dweb:/ipfs/QmTEwDbjJNxmMNCDMqtuou3dyM8Wtp8Q9NFvn7SAVM7Jf3"]},"@openzeppelin/contracts/access/roles/MinterRole.sol":{"keccak256":"0xbe8eef6f2cb4e427f5c5d8a76865ccd06e55a4f1d6671ba312d45bfa705aedbf","urls":["bzz-raw://badf338a5e22c8658c01fe2ce89b487d9dbf6d2d9d5eb49df7415383e2498765","dweb:/ipfs/QmP5aMkvFwMJyuQjKE8ADh5tkWYqonb4KjgkAjgYEVVFAv"]},"@openzeppelin/contracts/math/SafeMath.sol":{"keccak256":"0x640b6dee7a4b830bdfd52b5031a07fc2b12209f5b2e29e5d364a7d37f69d8076","urls":["bzz-raw://31113152e1ddb78fe7a4197f247591ca894e93f916867beb708d8e747b6cc74f","dweb:/ipfs/QmbZaJyXdpsYGykVhHH9qpVGQg9DGCxE2QufbCUy3daTgq"]},"@openzeppelin/contracts/token/ERC20/ERC20.sol":{"keccak256":"0xb15af804e2bc97db51e4e103f13de9fe13f87e6b835d7a88c897966c0e58506e","urls":["bzz-raw://1e8cff8437557fc915a3bed968fcd8f2df9809599e665ef69c2c9ce628548055","dweb:/ipfs/QmP5spYP8vs2jvLF8zNrXUbqB79hMsoEvMHiLcBxerWKcm"]},"@openzeppelin/contracts/token/ERC20/ERC20Burnable.sol":{"keccak256":"0x9b94356691f3cbf90a5d83ae3fdf6a5a662bb004d2bd8b034160d60221807e64","urls":["bzz-raw://14a9d33db455302b8bb9fdb28998efefbe5a1cb41b29e31391609f646b2ab351","dweb:/ipfs/Qmd4wFr6GEMZnnxgXeq3gmp7cs8tqDuHp3TVNhCbjnux2V"]},"@openzeppelin/contracts/token/ERC20/ERC20Detailed.sol":{"keccak256":"0x4a3a810b7ebe742e897e1fd428b3eeed2196d3acea58eaf9c566ed10d545d2ed","urls":["bzz-raw://28d03f328e19e4099d5013de0649afaeaabac1a614e130767ab1cb4aca9775b6","dweb:/ipfs/Qmb9iW7yNuYehB2NfhRMs9TakqbLVQhBhmGMkaDZ5g1Eb4"]},"@openzeppelin/contracts/token/ERC20/ERC20Mintable.sol":{"keccak256":"0xa2b957cf89692c504962afb7506999155f83385373f808243246cd5879de5940","urls":["bzz-raw://c44ae0ad1bce141c33b962f8b4e9228bdf8df36c8ac363b4f0bf9498b8bfc32a","dweb:/ipfs/QmcSBRdFwVvy2wFZrBwo5cvqNP4UHh9Eyzf8jnxBgnPqfe"]},"@openzeppelin/contracts/token/ERC20/IERC20.sol":{"keccak256":"0xe5bb0f57cff3e299f360052ba50f1ea0fff046df2be070b6943e0e3c3fdad8a9","urls":["bzz-raw://59fd025151435da35faa8093a5c7a17de02de9d08ad27275c5cdf05050820d91","dweb:/ipfs/QmQMvwEcPhoRXzbXyrdoeRtvLoifUW9Qh7Luho7bmUPRkc"]},"project:/src/contracts/MindsToken.sol":{"keccak256":"0x0c39e2ee264fdc11979067cd78ae0f5a3f6506be7ee287531f4af84611c708bf","urls":["bzz-raw://081c3f050e6e6a6d676924957b583f946c7c982cbe33ab58827a021bc63e7149","dweb:/ipfs/QmQiTHDJQ6y2b2wj98wJXc3bW75UGKTMPhPnuL3DwrfyFv"]}},"version":1}',
            'bytecode' => '0x60806040523480156200001157600080fd5b50604051620021e8380380620021e8833981810160405260608110156200003757600080fd5b81019080805160405193929190846401000000008211156200005857600080fd5b838201915060208201858111156200006f57600080fd5b82518660018202830111640100000000821117156200008d57600080fd5b8083526020830192505050908051906020019080838360005b83811015620000c3578082015181840152602081019050620000a6565b50505050905090810190601f168015620000f15780820380516001836020036101000a031916815260200191505b50604052602001805160405193929190846401000000008211156200011557600080fd5b838201915060208201858111156200012c57600080fd5b82518660018202830111640100000000821117156200014a57600080fd5b8083526020830192505050908051906020019080838360005b838110156200018057808201518184015260208101905062000163565b50505050905090810190601f168015620001ae5780820380516001836020036101000a031916815260200191505b5060405260200180519060200190929190505050828260128260039080519060200190620001de9291906200046c565b508160049080519060200190620001f79291906200046c565b5080600560006101000a81548160ff021916908360ff160217905550505050620002366200022a6200023f60201b60201c565b6200024760201b60201c565b5050506200051b565b600033905090565b62000262816006620002a860201b620018f31790919060201c565b8073ffffffffffffffffffffffffffffffffffffffff167f6ae172837ea30b801fbfcdd4108aa1d5bf8ff775444fd70256b44e6bf3dfc3f660405160405180910390a250565b620002ba82826200038c60201b60201c565b156200032e576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601f8152602001807f526f6c65733a206163636f756e7420616c72656164792068617320726f6c650081525060200191505060405180910390fd5b60018260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006101000a81548160ff0219169083151502179055505050565b60008073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff16141562000415576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526022815260200180620021c66022913960400191505060405180910390fd5b8260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060009054906101000a900460ff16905092915050565b828054600181600116156101000203166002900490600052602060002090601f016020900481019282601f10620004af57805160ff1916838001178555620004e0565b82800160010185558215620004e0579182015b82811115620004df578251825591602001919060010190620004c2565b5b509050620004ef9190620004f3565b5090565b6200051891905b8082111562000514576000816000905550600101620004fa565b5090565b90565b611c9b806200052b6000396000f3fe608060405234801561001057600080fd5b506004361061010b5760003560e01c806370a08231116100a257806398650275116100715780639865027514610528578063a457c2d714610532578063a9059cbb14610598578063aa271e1a146105fe578063dd62ed3e1461065a5761010b565b806370a08231146103bb57806379cc67901461041357806395d89b4114610461578063983b2d56146104e45761010b565b8063313ce567116100de578063313ce5671461029d57806339509351146102c157806340c10f191461032757806342966c681461038d5761010b565b806306fdde0314610110578063095ea7b31461019357806318160ddd146101f957806323b872dd14610217575b600080fd5b6101186106d2565b6040518080602001828103825283818151815260200191508051906020019080838360005b8381101561015857808201518184015260208101905061013d565b50505050905090810190601f1680156101855780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b6101df600480360360408110156101a957600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610774565b604051808215151515815260200191505060405180910390f35b610201610792565b6040518082815260200191505060405180910390f35b6102836004803603606081101561022d57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190803573ffffffffffffffffffffffffffffffffffffffff1690602001909291908035906020019092919050505061079c565b604051808215151515815260200191505060405180910390f35b6102a5610875565b604051808260ff1660ff16815260200191505060405180910390f35b61030d600480360360408110156102d757600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff1690602001909291908035906020019092919050505061088c565b604051808215151515815260200191505060405180910390f35b6103736004803603604081101561033d57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff1690602001909291908035906020019092919050505061093f565b604051808215151515815260200191505060405180910390f35b6103b9600480360360208110156103a357600080fd5b81019080803590602001909291905050506109ba565b005b6103fd600480360360208110156103d157600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff1690602001909291905050506109ce565b6040518082815260200191505060405180910390f35b61045f6004803603604081101561042957600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610a16565b005b610469610a24565b6040518080602001828103825283818151815260200191508051906020019080838360005b838110156104a957808201518184015260208101905061048e565b50505050905090810190601f1680156104d65780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b610526600480360360208110156104fa57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190505050610ac6565b005b610530610b37565b005b61057e6004803603604081101561054857600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610b49565b604051808215151515815260200191505060405180910390f35b6105e4600480360360408110156105ae57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610c16565b604051808215151515815260200191505060405180910390f35b6106406004803603602081101561061457600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190505050610c34565b604051808215151515815260200191505060405180910390f35b6106bc6004803603604081101561067057600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190803573ffffffffffffffffffffffffffffffffffffffff169060200190929190505050610c51565b6040518082815260200191505060405180910390f35b606060038054600181600116156101000203166002900480601f01602080910402602001604051908101604052809291908181526020018280546001816001161561010002031660029004801561076a5780601f1061073f5761010080835404028352916020019161076a565b820191906000526020600020905b81548152906001019060200180831161074d57829003601f168201915b5050505050905090565b6000610788610781610cd8565b8484610ce0565b6001905092915050565b6000600254905090565b60006107a9848484610ed7565b61086a846107b5610cd8565b61086585604051806060016040528060288152602001611b6a60289139600160008b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020600061081b610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b610ce0565b600190509392505050565b6000600560009054906101000a900460ff16905090565b6000610935610899610cd8565b8461093085600160006108aa610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008973ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461124d90919063ffffffff16565b610ce0565b6001905092915050565b600061095161094c610cd8565b610c34565b6109a6576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526030815260200180611b196030913960400191505060405180910390fd5b6109b083836112d5565b6001905092915050565b6109cb6109c5610cd8565b82611490565b50565b60008060008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020549050919050565b610a208282611648565b5050565b606060048054600181600116156101000203166002900480601f016020809104026020016040519081016040528092919081815260200182805460018160011615610100020316600290048015610abc5780601f10610a9157610100808354040283529160200191610abc565b820191906000526020600020905b815481529060010190602001808311610a9f57829003601f168201915b5050505050905090565b610ad6610ad1610cd8565b610c34565b610b2b576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526030815260200180611b196030913960400191505060405180910390fd5b610b3481611717565b50565b610b47610b42610cd8565b611771565b565b6000610c0c610b56610cd8565b84610c0785604051806060016040528060258152602001611c426025913960016000610b80610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008a73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b610ce0565b6001905092915050565b6000610c2a610c23610cd8565b8484610ed7565b6001905092915050565b6000610c4a8260066117cb90919063ffffffff16565b9050919050565b6000600160008473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002054905092915050565b600033905090565b600073ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff161415610d66576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526024815260200180611c1e6024913960400191505060405180910390fd5b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415610dec576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526022815260200180611ad16022913960400191505060405180910390fd5b80600160008573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508173ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff167f8c5be1e5ebec7d5bd14f71427d1e84f3dd0314c0f7b2291e5b200ac8c7c3b925836040518082815260200191505060405180910390a3505050565b600073ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff161415610f5d576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526025815260200180611bf96025913960400191505060405180910390fd5b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415610fe3576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526023815260200180611a8c6023913960400191505060405180910390fd5b61104e81604051806060016040528060268152602001611af3602691396000808773ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b6000808573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055506110e1816000808573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461124d90919063ffffffff16565b6000808473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508173ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a3505050565b600083831115829061123a576040517f08c379a00000000000000000000000000000000000000000000000000000000081526004018080602001828103825283818151815260200191508051906020019080838360005b838110156111ff5780820151818401526020810190506111e4565b50505050905090810190601f16801561122c5780820380516001836020036101000a031916815260200191505b509250505060405180910390fd5b5060008385039050809150509392505050565b6000808284019050838110156112cb576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601b8152602001807f536166654d6174683a206164646974696f6e206f766572666c6f77000000000081525060200191505060405180910390fd5b8091505092915050565b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415611378576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601f8152602001807f45524332303a206d696e7420746f20746865207a65726f20616464726573730081525060200191505060405180910390fd5b61138d8160025461124d90919063ffffffff16565b6002819055506113e4816000808573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461124d90919063ffffffff16565b6000808473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508173ffffffffffffffffffffffffffffffffffffffff16600073ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a35050565b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415611516576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526021815260200180611bd86021913960400191505060405180910390fd5b61158181604051806060016040528060228152602001611aaf602291396000808673ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b6000808473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055506115d8816002546118a990919063ffffffff16565b600281905550600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a35050565b6116528282611490565b6117138261165e610cd8565b61170e84604051806060016040528060248152602001611bb460249139600160008973ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006116c4610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b610ce0565b5050565b61172b8160066118f390919063ffffffff16565b8073ffffffffffffffffffffffffffffffffffffffff167f6ae172837ea30b801fbfcdd4108aa1d5bf8ff775444fd70256b44e6bf3dfc3f660405160405180910390a250565b6117858160066119ce90919063ffffffff16565b8073ffffffffffffffffffffffffffffffffffffffff167fe94479a9f7e1952cc78f2d6baab678adc1b772d936c6583def489e524cb6669260405160405180910390a250565b60008073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415611852576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526022815260200180611b926022913960400191505060405180910390fd5b8260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060009054906101000a900460ff16905092915050565b60006118eb83836040518060400160405280601e81526020017f536166654d6174683a207375627472616374696f6e206f766572666c6f77000081525061118d565b905092915050565b6118fd82826117cb565b15611970576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601f8152602001807f526f6c65733a206163636f756e7420616c72656164792068617320726f6c650081525060200191505060405180910390fd5b60018260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006101000a81548160ff0219169083151502179055505050565b6119d882826117cb565b611a2d576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526021815260200180611b496021913960400191505060405180910390fd5b60008260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006101000a81548160ff021916908315150217905550505056fe45524332303a207472616e7366657220746f20746865207a65726f206164647265737345524332303a206275726e20616d6f756e7420657863656564732062616c616e636545524332303a20617070726f766520746f20746865207a65726f206164647265737345524332303a207472616e7366657220616d6f756e7420657863656564732062616c616e63654d696e746572526f6c653a2063616c6c657220646f6573206e6f74206861766520746865204d696e74657220726f6c65526f6c65733a206163636f756e7420646f6573206e6f74206861766520726f6c6545524332303a207472616e7366657220616d6f756e74206578636565647320616c6c6f77616e6365526f6c65733a206163636f756e7420697320746865207a65726f206164647265737345524332303a206275726e20616d6f756e74206578636565647320616c6c6f77616e636545524332303a206275726e2066726f6d20746865207a65726f206164647265737345524332303a207472616e736665722066726f6d20746865207a65726f206164647265737345524332303a20617070726f76652066726f6d20746865207a65726f206164647265737345524332303a2064656372656173656420616c6c6f77616e63652062656c6f77207a65726fa265627a7a72315820601734823f13bd6d0a5aa6357482d94c926d1d18071dc08e4df7b975f90463ea64736f6c63430005110032526f6c65733a206163636f756e7420697320746865207a65726f2061646472657373',
            'deployedBytecode' => '0x608060405234801561001057600080fd5b506004361061010b5760003560e01c806370a08231116100a257806398650275116100715780639865027514610528578063a457c2d714610532578063a9059cbb14610598578063aa271e1a146105fe578063dd62ed3e1461065a5761010b565b806370a08231146103bb57806379cc67901461041357806395d89b4114610461578063983b2d56146104e45761010b565b8063313ce567116100de578063313ce5671461029d57806339509351146102c157806340c10f191461032757806342966c681461038d5761010b565b806306fdde0314610110578063095ea7b31461019357806318160ddd146101f957806323b872dd14610217575b600080fd5b6101186106d2565b6040518080602001828103825283818151815260200191508051906020019080838360005b8381101561015857808201518184015260208101905061013d565b50505050905090810190601f1680156101855780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b6101df600480360360408110156101a957600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610774565b604051808215151515815260200191505060405180910390f35b610201610792565b6040518082815260200191505060405180910390f35b6102836004803603606081101561022d57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190803573ffffffffffffffffffffffffffffffffffffffff1690602001909291908035906020019092919050505061079c565b604051808215151515815260200191505060405180910390f35b6102a5610875565b604051808260ff1660ff16815260200191505060405180910390f35b61030d600480360360408110156102d757600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff1690602001909291908035906020019092919050505061088c565b604051808215151515815260200191505060405180910390f35b6103736004803603604081101561033d57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff1690602001909291908035906020019092919050505061093f565b604051808215151515815260200191505060405180910390f35b6103b9600480360360208110156103a357600080fd5b81019080803590602001909291905050506109ba565b005b6103fd600480360360208110156103d157600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff1690602001909291905050506109ce565b6040518082815260200191505060405180910390f35b61045f6004803603604081101561042957600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610a16565b005b610469610a24565b6040518080602001828103825283818151815260200191508051906020019080838360005b838110156104a957808201518184015260208101905061048e565b50505050905090810190601f1680156104d65780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b610526600480360360208110156104fa57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190505050610ac6565b005b610530610b37565b005b61057e6004803603604081101561054857600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610b49565b604051808215151515815260200191505060405180910390f35b6105e4600480360360408110156105ae57600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff16906020019092919080359060200190929190505050610c16565b604051808215151515815260200191505060405180910390f35b6106406004803603602081101561061457600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190505050610c34565b604051808215151515815260200191505060405180910390f35b6106bc6004803603604081101561067057600080fd5b81019080803573ffffffffffffffffffffffffffffffffffffffff169060200190929190803573ffffffffffffffffffffffffffffffffffffffff169060200190929190505050610c51565b6040518082815260200191505060405180910390f35b606060038054600181600116156101000203166002900480601f01602080910402602001604051908101604052809291908181526020018280546001816001161561010002031660029004801561076a5780601f1061073f5761010080835404028352916020019161076a565b820191906000526020600020905b81548152906001019060200180831161074d57829003601f168201915b5050505050905090565b6000610788610781610cd8565b8484610ce0565b6001905092915050565b6000600254905090565b60006107a9848484610ed7565b61086a846107b5610cd8565b61086585604051806060016040528060288152602001611b6a60289139600160008b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020600061081b610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b610ce0565b600190509392505050565b6000600560009054906101000a900460ff16905090565b6000610935610899610cd8565b8461093085600160006108aa610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008973ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461124d90919063ffffffff16565b610ce0565b6001905092915050565b600061095161094c610cd8565b610c34565b6109a6576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526030815260200180611b196030913960400191505060405180910390fd5b6109b083836112d5565b6001905092915050565b6109cb6109c5610cd8565b82611490565b50565b60008060008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020549050919050565b610a208282611648565b5050565b606060048054600181600116156101000203166002900480601f016020809104026020016040519081016040528092919081815260200182805460018160011615610100020316600290048015610abc5780601f10610a9157610100808354040283529160200191610abc565b820191906000526020600020905b815481529060010190602001808311610a9f57829003601f168201915b5050505050905090565b610ad6610ad1610cd8565b610c34565b610b2b576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526030815260200180611b196030913960400191505060405180910390fd5b610b3481611717565b50565b610b47610b42610cd8565b611771565b565b6000610c0c610b56610cd8565b84610c0785604051806060016040528060258152602001611c426025913960016000610b80610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008a73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b610ce0565b6001905092915050565b6000610c2a610c23610cd8565b8484610ed7565b6001905092915050565b6000610c4a8260066117cb90919063ffffffff16565b9050919050565b6000600160008473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002054905092915050565b600033905090565b600073ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff161415610d66576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526024815260200180611c1e6024913960400191505060405180910390fd5b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415610dec576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526022815260200180611ad16022913960400191505060405180910390fd5b80600160008573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508173ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff167f8c5be1e5ebec7d5bd14f71427d1e84f3dd0314c0f7b2291e5b200ac8c7c3b925836040518082815260200191505060405180910390a3505050565b600073ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff161415610f5d576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526025815260200180611bf96025913960400191505060405180910390fd5b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415610fe3576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526023815260200180611a8c6023913960400191505060405180910390fd5b61104e81604051806060016040528060268152602001611af3602691396000808773ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b6000808573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055506110e1816000808573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461124d90919063ffffffff16565b6000808473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508173ffffffffffffffffffffffffffffffffffffffff168373ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a3505050565b600083831115829061123a576040517f08c379a00000000000000000000000000000000000000000000000000000000081526004018080602001828103825283818151815260200191508051906020019080838360005b838110156111ff5780820151818401526020810190506111e4565b50505050905090810190601f16801561122c5780820380516001836020036101000a031916815260200191505b509250505060405180910390fd5b5060008385039050809150509392505050565b6000808284019050838110156112cb576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601b8152602001807f536166654d6174683a206164646974696f6e206f766572666c6f77000000000081525060200191505060405180910390fd5b8091505092915050565b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415611378576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601f8152602001807f45524332303a206d696e7420746f20746865207a65726f20616464726573730081525060200191505060405180910390fd5b61138d8160025461124d90919063ffffffff16565b6002819055506113e4816000808573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461124d90919063ffffffff16565b6000808473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508173ffffffffffffffffffffffffffffffffffffffff16600073ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a35050565b600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415611516576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526021815260200180611bd86021913960400191505060405180910390fd5b61158181604051806060016040528060228152602001611aaf602291396000808673ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b6000808473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055506115d8816002546118a990919063ffffffff16565b600281905550600073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a35050565b6116528282611490565b6117138261165e610cd8565b61170e84604051806060016040528060248152602001611bb460249139600160008973ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006116c4610cd8565b73ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205461118d9092919063ffffffff16565b610ce0565b5050565b61172b8160066118f390919063ffffffff16565b8073ffffffffffffffffffffffffffffffffffffffff167f6ae172837ea30b801fbfcdd4108aa1d5bf8ff775444fd70256b44e6bf3dfc3f660405160405180910390a250565b6117858160066119ce90919063ffffffff16565b8073ffffffffffffffffffffffffffffffffffffffff167fe94479a9f7e1952cc78f2d6baab678adc1b772d936c6583def489e524cb6669260405160405180910390a250565b60008073ffffffffffffffffffffffffffffffffffffffff168273ffffffffffffffffffffffffffffffffffffffff161415611852576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526022815260200180611b926022913960400191505060405180910390fd5b8260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060009054906101000a900460ff16905092915050565b60006118eb83836040518060400160405280601e81526020017f536166654d6174683a207375627472616374696f6e206f766572666c6f77000081525061118d565b905092915050565b6118fd82826117cb565b15611970576040517f08c379a000000000000000000000000000000000000000000000000000000000815260040180806020018281038252601f8152602001807f526f6c65733a206163636f756e7420616c72656164792068617320726f6c650081525060200191505060405180910390fd5b60018260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006101000a81548160ff0219169083151502179055505050565b6119d882826117cb565b611a2d576040517f08c379a0000000000000000000000000000000000000000000000000000000008152600401808060200182810382526021815260200180611b496021913960400191505060405180910390fd5b60008260000160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060006101000a81548160ff021916908315150217905550505056fe45524332303a207472616e7366657220746f20746865207a65726f206164647265737345524332303a206275726e20616d6f756e7420657863656564732062616c616e636545524332303a20617070726f766520746f20746865207a65726f206164647265737345524332303a207472616e7366657220616d6f756e7420657863656564732062616c616e63654d696e746572526f6c653a2063616c6c657220646f6573206e6f74206861766520746865204d696e74657220726f6c65526f6c65733a206163636f756e7420646f6573206e6f74206861766520726f6c6545524332303a207472616e7366657220616d6f756e74206578636565647320616c6c6f77616e6365526f6c65733a206163636f756e7420697320746865207a65726f206164647265737345524332303a206275726e20616d6f756e74206578636565647320616c6c6f77616e636545524332303a206275726e2066726f6d20746865207a65726f206164647265737345524332303a207472616e736665722066726f6d20746865207a65726f206164647265737345524332303a20617070726f76652066726f6d20746865207a65726f206164647265737345524332303a2064656372656173656420616c6c6f77616e63652062656c6f77207a65726fa265627a7a72315820601734823f13bd6d0a5aa6357482d94c926d1d18071dc08e4df7b975f90463ea64736f6c63430005110032',
            'sourceMap' => '219:235:9:-;;;293:159;8:9:-1;5:2;;;30:1;27;20:12;5:2;293:159:9;;;;;;;;;;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;293:159:9;;;;;;;;;;;;;19:11:-1;14:3;11:20;8:2;;;44:1;41;34:12;8:2;71:11;66:3;62:21;55:28;;123:4;118:3;114:14;159:9;141:16;138:31;135:2;;;182:1;179;172:12;135:2;219:3;213:10;330:9;325:1;311:12;307:20;289:16;285:43;282:58;261:11;247:12;244:29;233:115;230:2;;;361:1;358;351:12;230:2;384:12;379:3;372:25;420:4;415:3;411:14;404:21;;0:432;;293:159:9;;;;;;;;;;23:1:-1;8:100;33:3;30:1;27:10;8:100;;;99:1;94:3;90:11;84:18;80:1;75:3;71:11;64:39;52:2;49:1;45:10;40:15;;8:100;;;12:14;293:159:9;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;19:11:-1;14:3;11:20;8:2;;;44:1;41;34:12;8:2;71:11;66:3;62:21;55:28;;123:4;118:3;114:14;159:9;141:16;138:31;135:2;;;182:1;179;172:12;135:2;219:3;213:10;330:9;325:1;311:12;307:20;289:16;285:43;282:58;261:11;247:12;244:29;233:115;230:2;;;361:1;358;351:12;230:2;384:12;379:3;372:25;420:4;415:3;411:14;404:21;;0:432;;293:159:9;;;;;;;;;;23:1:-1;8:100;33:3;30:1;27:10;8:100;;;99:1;94:3;90:11;84:18;80:1;75:3;71:11;64:39;52:2;49:1;45:10;40:15;;8:100;;;12:14;293:159:9;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;410:5;417:7;426:2;512:4:6;504:5;:12;;;;;;;;;;;;:::i;:::-;;536:6;526:7;:16;;;;;;;;;;;;:::i;:::-;;564:8;552:9;;:20;;;;;;;;;;;;;;;;;;416:163;;;318:24:2;329:12;:10;;;:12;;:::i;:::-;318:10;;;:24;;:::i;:::-;293:159:9;;;219:235;;788:96:0;833:15;867:10;860:17;;788:96;:::o;786:119:2:-;842:21;855:7;842:8;:12;;;;;;:21;;;;:::i;:::-;890:7;878:20;;;;;;;;;;;;786:119;:::o;260:175:1:-;337:18;341:4;347:7;337:3;;;:18;;:::i;:::-;336:19;328:63;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;424:4;401;:11;;:20;413:7;401:20;;;;;;;;;;;;;;;;:27;;;;;;;;;;;;;;;;;;260:175;;:::o;779:200::-;851:4;894:1;875:21;;:7;:21;;;;867:68;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;952:4;:11;;:20;964:7;952:20;;;;;;;;;;;;;;;;;;;;;;;;;945:27;;779:200;;;;:::o;219:235:9:-;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;:::o;:::-;;;;;;;;;;;;;;;;;;;;;;;;;;;:::o;:::-;;;;;;;',
            'deployedSourceMap' => '219:235:9:-;;;;8:9:-1;5:2;;;30:1;27;20:12;5:2;219:235:9;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;644:81:6;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;23:1:-1;8:100;33:3;30:1;27:10;8:100;;;99:1;94:3;90:11;84:18;80:1;75:3;71:11;64:39;52:2;49:1;45:10;40:15;;8:100;;;12:14;644:81:6;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;2500:149:4;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;2500:149:4;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;1559:89;;;:::i;:::-;;;;;;;;;;;;;;;;;;;3107:300;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;3107:300:4;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;1472:81:6;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;3802:207:4;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;3802:207:4;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;502:140:7;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;502:140:7;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;439:81:5;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;439:81:5;;;;;;;;;;;;;;;;;:::i;:::-;;1706:108:4;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;1706:108:4;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;577:101:5;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;577:101:5;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;838:85:6;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;23:1:-1;8:100;33:3;30:1;27:10;8:100;;;99:1;94:3;90:11;84:18;80:1;75:3;71:11;64:39;52:2;49:1;45:10;40:15;;8:100;;;12:14;838:85:6;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;607:90:2;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;607:90:2;;;;;;;;;;;;;;;;;;;:::i;:::-;;703:77;;;:::i;:::-;;4496:258:4;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;4496:258:4;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;2017:155;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;2017:155:4;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;494:107:2;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;494:107:2;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;;;;;2230:132:4;;;;;;13:2:-1;8:3;5:11;2:2;;;29:1;26;19:12;2:2;2230:132:4;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;:::i;:::-;;;;;;;;;;;;;;;;;;;644:81:6;681:13;713:5;706:12;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;644:81;:::o;2500:149:4:-;2566:4;2582:39;2591:12;:10;:12::i;:::-;2605:7;2614:6;2582:8;:39::i;:::-;2638:4;2631:11;;2500:149;;;;:::o;1559:89::-;1603:7;1629:12;;1622:19;;1559:89;:::o;3107:300::-;3196:4;3212:36;3222:6;3230:9;3241:6;3212:9;:36::i;:::-;3258:121;3267:6;3275:12;:10;:12::i;:::-;3289:89;3327:6;3289:89;;;;;;;;;;;;;;;;;:11;:19;3301:6;3289:19;;;;;;;;;;;;;;;:33;3309:12;:10;:12::i;:::-;3289:33;;;;;;;;;;;;;;;;:37;;:89;;;;;:::i;:::-;3258:8;:121::i;:::-;3396:4;3389:11;;3107:300;;;;;:::o;1472:81:6:-;1513:5;1537:9;;;;;;;;;;;1530:16;;1472:81;:::o;3802:207:4:-;3882:4;3898:83;3907:12;:10;:12::i;:::-;3921:7;3930:50;3969:10;3930:11;:25;3942:12;:10;:12::i;:::-;3930:25;;;;;;;;;;;;;;;:34;3956:7;3930:34;;;;;;;;;;;;;;;;:38;;:50;;;;:::i;:::-;3898:8;:83::i;:::-;3998:4;3991:11;;3802:207;;;;:::o;502:140:7:-;576:4;395:22:2;404:12;:10;:12::i;:::-;395:8;:22::i;:::-;387:83;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;592:22:7;598:7;607:6;592:5;:22::i;:::-;631:4;624:11;;502:140;;;;:::o;439:81:5:-;486:27;492:12;:10;:12::i;:::-;506:6;486:5;:27::i;:::-;439:81;:::o;1706:108:4:-;1763:7;1789:9;:18;1799:7;1789:18;;;;;;;;;;;;;;;;1782:25;;1706:108;;;:::o;577:101:5:-;645:26;655:7;664:6;645:9;:26::i;:::-;577:101;;:::o;838:85:6:-;877:13;909:7;902:14;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;838:85;:::o;607:90:2:-;395:22;404:12;:10;:12::i;:::-;395:8;:22::i;:::-;387:83;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;671:19;682:7;671:10;:19::i;:::-;607:90;:::o;703:77::-;746:27;760:12;:10;:12::i;:::-;746:13;:27::i;:::-;703:77::o;4496:258:4:-;4581:4;4597:129;4606:12;:10;:12::i;:::-;4620:7;4629:96;4668:15;4629:96;;;;;;;;;;;;;;;;;:11;:25;4641:12;:10;:12::i;:::-;4629:25;;;;;;;;;;;;;;;:34;4655:7;4629:34;;;;;;;;;;;;;;;;:38;;:96;;;;;:::i;:::-;4597:8;:129::i;:::-;4743:4;4736:11;;4496:258;;;;:::o;2017:155::-;2086:4;2102:42;2112:12;:10;:12::i;:::-;2126:9;2137:6;2102:9;:42::i;:::-;2161:4;2154:11;;2017:155;;;;:::o;494:107:2:-;550:4;573:21;586:7;573:8;:12;;:21;;;;:::i;:::-;566:28;;494:107;;;:::o;2230:132:4:-;2302:7;2328:11;:18;2340:5;2328:18;;;;;;;;;;;;;;;:27;2347:7;2328:27;;;;;;;;;;;;;;;;2321:34;;2230:132;;;;:::o;788:96:0:-;833:15;867:10;860:17;;788:96;:::o;7350:332:4:-;7460:1;7443:19;;:5;:19;;;;7435:68;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;7540:1;7521:21;;:7;:21;;;;7513:68;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;7622:6;7592:11;:18;7604:5;7592:18;;;;;;;;;;;;;;;:27;7611:7;7592:27;;;;;;;;;;;;;;;:36;;;;7659:7;7643:32;;7652:5;7643:32;;;7668:6;7643:32;;;;;;;;;;;;;;;;;;7350:332;;;:::o;5228:464::-;5343:1;5325:20;;:6;:20;;;;5317:70;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;5426:1;5405:23;;:9;:23;;;;5397:71;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;5499;5521:6;5499:71;;;;;;;;;;;;;;;;;:9;:17;5509:6;5499:17;;;;;;;;;;;;;;;;:21;;:71;;;;;:::i;:::-;5479:9;:17;5489:6;5479:17;;;;;;;;;;;;;;;:91;;;;5603:32;5628:6;5603:9;:20;5613:9;5603:20;;;;;;;;;;;;;;;;:24;;:32;;;;:::i;:::-;5580:9;:20;5590:9;5580:20;;;;;;;;;;;;;;;:55;;;;5667:9;5650:35;;5659:6;5650:35;;;5678:6;5650:35;;;;;;;;;;;;;;;;;;5228:464;;;:::o;1732:187:3:-;1818:7;1850:1;1845;:6;;1853:12;1837:29;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;23:1:-1;8:100;33:3;30:1;27:10;8:100;;;99:1;94:3;90:11;84:18;80:1;75:3;71:11;64:39;52:2;49:1;45:10;40:15;;8:100;;;12:14;1837:29:3;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;1876:9;1892:1;1888;:5;1876:17;;1911:1;1904:8;;;1732:187;;;;;:::o;834:176::-;892:7;911:9;927:1;923;:5;911:17;;951:1;946;:6;;938:46;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;1002:1;995:8;;;834:176;;;;:::o;5962:302:4:-;6056:1;6037:21;;:7;:21;;;;6029:65;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;6120:24;6137:6;6120:12;;:16;;:24;;;;:::i;:::-;6105:12;:39;;;;6175:30;6198:6;6175:9;:18;6185:7;6175:18;;;;;;;;;;;;;;;;:22;;:30;;;;:::i;:::-;6154:9;:18;6164:7;6154:18;;;;;;;;;;;;;;;:51;;;;6241:7;6220:37;;6237:1;6220:37;;;6250:6;6220:37;;;;;;;;;;;;;;;;;;5962:302;;:::o;6583:342::-;6677:1;6658:21;;:7;:21;;;;6650:67;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;6749:68;6772:6;6749:68;;;;;;;;;;;;;;;;;:9;:18;6759:7;6749:18;;;;;;;;;;;;;;;;:22;;:68;;;;;:::i;:::-;6728:9;:18;6738:7;6728:18;;;;;;;;;;;;;;;:89;;;;6842:24;6859:6;6842:12;;:16;;:24;;;;:::i;:::-;6827:12;:39;;;;6907:1;6881:37;;6890:7;6881:37;;;6911:6;6881:37;;;;;;;;;;;;;;;;;;6583:342;;:::o;7860:229::-;7931:22;7937:7;7946:6;7931:5;:22::i;:::-;7963:119;7972:7;7981:12;:10;:12::i;:::-;7995:86;8034:6;7995:86;;;;;;;;;;;;;;;;;:11;:20;8007:7;7995:20;;;;;;;;;;;;;;;:34;8016:12;:10;:12::i;:::-;7995:34;;;;;;;;;;;;;;;;:38;;:86;;;;;:::i;:::-;7963:8;:119::i;:::-;7860:229;;:::o;786:119:2:-;842:21;855:7;842:8;:12;;:21;;;;:::i;:::-;890:7;878:20;;;;;;;;;;;;786:119;:::o;911:127::-;970:24;986:7;970:8;:15;;:24;;;;:::i;:::-;1023:7;1009:22;;;;;;;;;;;;911:127;:::o;779:200:1:-;851:4;894:1;875:21;;:7;:21;;;;867:68;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;952:4;:11;;:20;964:7;952:20;;;;;;;;;;;;;;;;;;;;;;;;;945:27;;779:200;;;;:::o;1274:134:3:-;1332:7;1358:43;1362:1;1365;1358:43;;;;;;;;;;;;;;;;;:3;:43::i;:::-;1351:50;;1274:134;;;;:::o;260:175:1:-;337:18;341:4;347:7;337:3;:18::i;:::-;336:19;328:63;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;424:4;401;:11;;:20;413:7;401:20;;;;;;;;;;;;;;;;:27;;;;;;;;;;;;;;;;;;260:175;;:::o;510:180::-;589:18;593:4;599:7;589:3;:18::i;:::-;581:64;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;678:5;655:4;:11;;:20;667:7;655:20;;;;;;;;;;;;;;;;:28;;;;;;;;;;;;;;;;;;510:180;;:::o',
            'source' => 'pragma solidity ^0.5.17;
          
          import "@openzeppelin/contracts/token/ERC20/ERC20Burnable.sol";
          import "@openzeppelin/contracts/token/ERC20/ERC20Detailed.sol";
          import "@openzeppelin/contracts/token/ERC20/ERC20Mintable.sol";
          
          contract MindsToken is ERC20Burnable, ERC20Detailed, ERC20Mintable {
          
              constructor(
                string memory _name,
                string memory _symbol,
                uint256 _decimals
              )
              ERC20Detailed(_name, _symbol, 18)
              public
              {
              }
          }
          ',
            'sourcePath' => '/home/nemo/work/minds/token-skale/src/contracts/MindsToken.sol',
            'ast' =>
            [
              'absolutePath' => 'project:/src/contracts/MindsToken.sol',
              'exportedSymbols' =>
              [
                'MindsToken' =>
                [
                  0 => 1022,
                ],
              ],
              'id' => 1023,
              'nodeType' => 'SourceUnit',
              'nodes' =>
              [
                0 =>
                [
                  'id' => 997,
                  'literals' =>
                  [
                    0 => 'solidity',
                    1 => '^',
                    2 => '0.5',
                    3 => '.17',
                  ],
                  'nodeType' => 'PragmaDirective',
                  'src' => '0:24:9',
                ],
                1 =>
                [
                  'absolutePath' => '@openzeppelin/contracts/token/ERC20/ERC20Burnable.sol',
                  'file' => '@openzeppelin/contracts/token/ERC20/ERC20Burnable.sol',
                  'id' => 998,
                  'nodeType' => 'ImportDirective',
                  'scope' => 1023,
                  'sourceUnit' => 841,
                  'src' => '26:63:9',
                  'symbolAliases' =>
                  [
                  ],
                  'unitAlias' => '',
                ],
                2 =>
                [
                  'absolutePath' => '@openzeppelin/contracts/token/ERC20/ERC20Detailed.sol',
                  'file' => '@openzeppelin/contracts/token/ERC20/ERC20Detailed.sol',
                  'id' => 999,
                  'nodeType' => 'ImportDirective',
                  'scope' => 1023,
                  'sourceUnit' => 899,
                  'src' => '90:63:9',
                  'symbolAliases' =>
                  [
                  ],
                  'unitAlias' => '',
                ],
                3 =>
                [
                  'absolutePath' => '@openzeppelin/contracts/token/ERC20/ERC20Mintable.sol',
                  'file' => '@openzeppelin/contracts/token/ERC20/ERC20Mintable.sol',
                  'id' => 1000,
                  'nodeType' => 'ImportDirective',
                  'scope' => 1023,
                  'sourceUnit' => 927,
                  'src' => '154:63:9',
                  'symbolAliases' =>
                  [
                  ],
                  'unitAlias' => '',
                ],
                4 =>
                [
                  'baseContracts' =>
                  [
                    0 =>
                    [
                      'arguments' => null,
                      'baseName' =>
                      [
                        'contractScope' => null,
                        'id' => 1001,
                        'name' => 'ERC20Burnable',
                        'nodeType' => 'UserDefinedTypeName',
                        'referencedDeclaration' => 840,
                        'src' => '242:13:9',
                        'typeDescriptions' =>
                        [
                          'typeIdentifier' => 't_contract$_ERC20Burnable_$840',
                          'typeString' => 'contract ERC20Burnable',
                        ],
                      ],
                      'id' => 1002,
                      'nodeType' => 'InheritanceSpecifier',
                      'src' => '242:13:9',
                    ],
                    1 =>
                    [
                      'arguments' => null,
                      'baseName' =>
                      [
                        'contractScope' => null,
                        'id' => 1003,
                        'name' => 'ERC20Detailed',
                        'nodeType' => 'UserDefinedTypeName',
                        'referencedDeclaration' => 898,
                        'src' => '257:13:9',
                        'typeDescriptions' =>
                        [
                          'typeIdentifier' => 't_contract$_ERC20Detailed_$898',
                          'typeString' => 'contract ERC20Detailed',
                        ],
                      ],
                      'id' => 1004,
                      'nodeType' => 'InheritanceSpecifier',
                      'src' => '257:13:9',
                    ],
                    2 =>
                    [
                      'arguments' => null,
                      'baseName' =>
                      [
                        'contractScope' => null,
                        'id' => 1005,
                        'name' => 'ERC20Mintable',
                        'nodeType' => 'UserDefinedTypeName',
                        'referencedDeclaration' => 926,
                        'src' => '272:13:9',
                        'typeDescriptions' =>
                        [
                          'typeIdentifier' => 't_contract$_ERC20Mintable_$926',
                          'typeString' => 'contract ERC20Mintable',
                        ],
                      ],
                      'id' => 1006,
                      'nodeType' => 'InheritanceSpecifier',
                      'src' => '272:13:9',
                    ],
                  ],
                  'contractDependencies' =>
                  [
                    0 => 26,
                    1 => 214,
                    2 => 806,
                    3 => 840,
                    4 => 898,
                    5 => 926,
                    6 => 995,
                  ],
                  'contractKind' => 'contract',
                  'documentation' => null,
                  'fullyImplemented' => true,
                  'id' => 1022,
                  'linearizedBaseContracts' =>
                  [
                    0 => 1022,
                    1 => 926,
                    2 => 214,
                    3 => 898,
                    4 => 840,
                    5 => 806,
                    6 => 995,
                    7 => 26,
                  ],
                  'name' => 'MindsToken',
                  'nodeType' => 'ContractDefinition',
                  'nodes' =>
                  [
                    0 =>
                    [
                      'body' =>
                      [
                        'id' => 1020,
                        'nodeType' => 'Block',
                        'src' => '445:7:9',
                        'statements' =>
                        [
                        ],
                      ],
                      'documentation' => null,
                      'id' => 1021,
                      'implemented' => true,
                      'kind' => 'constructor',
                      'modifiers' =>
                      [
                        0 =>
                        [
                          'arguments' =>
                          [
                            0 =>
                            [
                              'argumentTypes' => null,
                              'id' => 1015,
                              'name' => '_name',
                              'nodeType' => 'Identifier',
                              'overloadedDeclarations' =>
                              [
                              ],
                              'referencedDeclaration' => 1008,
                              'src' => '410:5:9',
                              'typeDescriptions' =>
                              [
                                'typeIdentifier' => 't_string_memory_ptr',
                                'typeString' => 'string memory',
                              ],
                            ],
                            1 =>
                            [
                              'argumentTypes' => null,
                              'id' => 1016,
                              'name' => '_symbol',
                              'nodeType' => 'Identifier',
                              'overloadedDeclarations' =>
                              [
                              ],
                              'referencedDeclaration' => 1010,
                              'src' => '417:7:9',
                              'typeDescriptions' =>
                              [
                                'typeIdentifier' => 't_string_memory_ptr',
                                'typeString' => 'string memory',
                              ],
                            ],
                            2 =>
                            [
                              'argumentTypes' => null,
                              'hexValue' => '3138',
                              'id' => 1017,
                              'isConstant' => false,
                              'isLValue' => false,
                              'isPure' => true,
                              'kind' => 'number',
                              'lValueRequested' => false,
                              'nodeType' => 'Literal',
                              'src' => '426:2:9',
                              'subdenomination' => null,
                              'typeDescriptions' =>
                              [
                                'typeIdentifier' => 't_rational_18_by_1',
                                'typeString' => 'int_const 18',
                              ],
                              'value' => '18',
                            ],
                          ],
                          'id' => 1018,
                          'modifierName' =>
                          [
                            'argumentTypes' => null,
                            'id' => 1014,
                            'name' => 'ERC20Detailed',
                            'nodeType' => 'Identifier',
                            'overloadedDeclarations' =>
                            [
                            ],
                            'referencedDeclaration' => 898,
                            'src' => '396:13:9',
                            'typeDescriptions' =>
                            [
                              'typeIdentifier' => 't_type$_t_contract$_ERC20Detailed_$898_$',
                              'typeString' => 'type(contract ERC20Detailed)',
                            ],
                          ],
                          'nodeType' => 'ModifierInvocation',
                          'src' => '396:33:9',
                        ],
                      ],
                      'name' => '',
                      'nodeType' => 'FunctionDefinition',
                      'parameters' =>
                      [
                        'id' => 1013,
                        'nodeType' => 'ParameterList',
                        'parameters' =>
                        [
                          0 =>
                          [
                            'constant' => false,
                            'id' => 1008,
                            'name' => '_name',
                            'nodeType' => 'VariableDeclaration',
                            'scope' => 1021,
                            'src' => '312:19:9',
                            'stateVariable' => false,
                            'storageLocation' => 'memory',
                            'typeDescriptions' =>
                            [
                              'typeIdentifier' => 't_string_memory_ptr',
                              'typeString' => 'string',
                            ],
                            'typeName' =>
                            [
                              'id' => 1007,
                              'name' => 'string',
                              'nodeType' => 'ElementaryTypeName',
                              'src' => '312:6:9',
                              'typeDescriptions' =>
                              [
                                'typeIdentifier' => 't_string_storage_ptr',
                                'typeString' => 'string',
                              ],
                            ],
                            'value' => null,
                            'visibility' => 'internal',
                          ],
                          1 =>
                          [
                            'constant' => false,
                            'id' => 1010,
                            'name' => '_symbol',
                            'nodeType' => 'VariableDeclaration',
                            'scope' => 1021,
                            'src' => '339:21:9',
                            'stateVariable' => false,
                            'storageLocation' => 'memory',
                            'typeDescriptions' =>
                            [
                              'typeIdentifier' => 't_string_memory_ptr',
                              'typeString' => 'string',
                            ],
                            'typeName' =>
                            [
                              'id' => 1009,
                              'name' => 'string',
                              'nodeType' => 'ElementaryTypeName',
                              'src' => '339:6:9',
                              'typeDescriptions' =>
                              [
                                'typeIdentifier' => 't_string_storage_ptr',
                                'typeString' => 'string',
                              ],
                            ],
                            'value' => null,
                            'visibility' => 'internal',
                          ],
                          2 =>
                          [
                            'constant' => false,
                            'id' => 1012,
                            'name' => '_decimals',
                            'nodeType' => 'VariableDeclaration',
                            'scope' => 1021,
                            'src' => '368:17:9',
                            'stateVariable' => false,
                            'storageLocation' => 'default',
                            'typeDescriptions' =>
                            [
                              'typeIdentifier' => 't_uint256',
                              'typeString' => 'uint256',
                            ],
                            'typeName' =>
                            [
                              'id' => 1011,
                              'name' => 'uint256',
                              'nodeType' => 'ElementaryTypeName',
                              'src' => '368:7:9',
                              'typeDescriptions' =>
                              [
                                'typeIdentifier' => 't_uint256',
                                'typeString' => 'uint256',
                              ],
                            ],
                            'value' => null,
                            'visibility' => 'internal',
                          ],
                        ],
                        'src' => '304:87:9',
                      ],
                      'returnParameters' =>
                      [
                        'id' => 1019,
                        'nodeType' => 'ParameterList',
                        'parameters' =>
                        [
                        ],
                        'src' => '445:0:9',
                      ],
                      'scope' => 1022,
                      'src' => '293:159:9',
                      'stateMutability' => 'nonpayable',
                      'superFunction' => null,
                      'visibility' => 'public',
                    ],
                  ],
                  'scope' => 1023,
                  'src' => '219:235:9',
                ],
              ],
              'src' => '0:455:9',
            ],
            'legacyAST' =>
            [
              'attributes' =>
              [
                'absolutePath' => 'project:/src/contracts/MindsToken.sol',
                'exportedSymbols' =>
                [
                  'MindsToken' =>
                  [
                    0 => 1022,
                  ],
                ],
              ],
              'children' =>
              [
                0 =>
                [
                  'attributes' =>
                  [
                    'literals' =>
                    [
                      0 => 'solidity',
                      1 => '^',
                      2 => '0.5',
                      3 => '.17',
                    ],
                  ],
                  'id' => 997,
                  'name' => 'PragmaDirective',
                  'src' => '0:24:9',
                ],
                1 =>
                [
                  'attributes' =>
                  [
                    'SourceUnit' => 841,
                    'absolutePath' => '@openzeppelin/contracts/token/ERC20/ERC20Burnable.sol',
                    'file' => '@openzeppelin/contracts/token/ERC20/ERC20Burnable.sol',
                    'scope' => 1023,
                    'symbolAliases' =>
                    [
                      0 => null,
                    ],
                    'unitAlias' => '',
                  ],
                  'id' => 998,
                  'name' => 'ImportDirective',
                  'src' => '26:63:9',
                ],
                2 =>
                [
                  'attributes' =>
                  [
                    'SourceUnit' => 899,
                    'absolutePath' => '@openzeppelin/contracts/token/ERC20/ERC20Detailed.sol',
                    'file' => '@openzeppelin/contracts/token/ERC20/ERC20Detailed.sol',
                    'scope' => 1023,
                    'symbolAliases' =>
                    [
                      0 => null,
                    ],
                    'unitAlias' => '',
                  ],
                  'id' => 999,
                  'name' => 'ImportDirective',
                  'src' => '90:63:9',
                ],
                3 =>
                [
                  'attributes' =>
                  [
                    'SourceUnit' => 927,
                    'absolutePath' => '@openzeppelin/contracts/token/ERC20/ERC20Mintable.sol',
                    'file' => '@openzeppelin/contracts/token/ERC20/ERC20Mintable.sol',
                    'scope' => 1023,
                    'symbolAliases' =>
                    [
                      0 => null,
                    ],
                    'unitAlias' => '',
                  ],
                  'id' => 1000,
                  'name' => 'ImportDirective',
                  'src' => '154:63:9',
                ],
                4 =>
                [
                  'attributes' =>
                  [
                    'contractDependencies' =>
                    [
                      0 => 26,
                      1 => 214,
                      2 => 806,
                      3 => 840,
                      4 => 898,
                      5 => 926,
                      6 => 995,
                    ],
                    'contractKind' => 'contract',
                    'documentation' => null,
                    'fullyImplemented' => true,
                    'linearizedBaseContracts' =>
                    [
                      0 => 1022,
                      1 => 926,
                      2 => 214,
                      3 => 898,
                      4 => 840,
                      5 => 806,
                      6 => 995,
                      7 => 26,
                    ],
                    'name' => 'MindsToken',
                    'scope' => 1023,
                  ],
                  'children' =>
                  [
                    0 =>
                    [
                      'attributes' =>
                      [
                        'arguments' => null,
                      ],
                      'children' =>
                      [
                        0 =>
                        [
                          'attributes' =>
                          [
                            'contractScope' => null,
                            'name' => 'ERC20Burnable',
                            'referencedDeclaration' => 840,
                            'type' => 'contract ERC20Burnable',
                          ],
                          'id' => 1001,
                          'name' => 'UserDefinedTypeName',
                          'src' => '242:13:9',
                        ],
                      ],
                      'id' => 1002,
                      'name' => 'InheritanceSpecifier',
                      'src' => '242:13:9',
                    ],
                    1 =>
                    [
                      'attributes' =>
                      [
                        'arguments' => null,
                      ],
                      'children' =>
                      [
                        0 =>
                        [
                          'attributes' =>
                          [
                            'contractScope' => null,
                            'name' => 'ERC20Detailed',
                            'referencedDeclaration' => 898,
                            'type' => 'contract ERC20Detailed',
                          ],
                          'id' => 1003,
                          'name' => 'UserDefinedTypeName',
                          'src' => '257:13:9',
                        ],
                      ],
                      'id' => 1004,
                      'name' => 'InheritanceSpecifier',
                      'src' => '257:13:9',
                    ],
                    2 =>
                    [
                      'attributes' =>
                      [
                        'arguments' => null,
                      ],
                      'children' =>
                      [
                        0 =>
                        [
                          'attributes' =>
                          [
                            'contractScope' => null,
                            'name' => 'ERC20Mintable',
                            'referencedDeclaration' => 926,
                            'type' => 'contract ERC20Mintable',
                          ],
                          'id' => 1005,
                          'name' => 'UserDefinedTypeName',
                          'src' => '272:13:9',
                        ],
                      ],
                      'id' => 1006,
                      'name' => 'InheritanceSpecifier',
                      'src' => '272:13:9',
                    ],
                    3 =>
                    [
                      'attributes' =>
                      [
                        'documentation' => null,
                        'implemented' => true,
                        'isConstructor' => true,
                        'kind' => 'constructor',
                        'name' => '',
                        'scope' => 1022,
                        'stateMutability' => 'nonpayable',
                        'superFunction' => null,
                        'visibility' => 'public',
                      ],
                      'children' =>
                      [
                        0 =>
                        [
                          'children' =>
                          [
                            0 =>
                            [
                              'attributes' =>
                              [
                                'constant' => false,
                                'name' => '_name',
                                'scope' => 1021,
                                'stateVariable' => false,
                                'storageLocation' => 'memory',
                                'type' => 'string',
                                'value' => null,
                                'visibility' => 'internal',
                              ],
                              'children' =>
                              [
                                0 =>
                                [
                                  'attributes' =>
                                  [
                                    'name' => 'string',
                                    'type' => 'string',
                                  ],
                                  'id' => 1007,
                                  'name' => 'ElementaryTypeName',
                                  'src' => '312:6:9',
                                ],
                              ],
                              'id' => 1008,
                              'name' => 'VariableDeclaration',
                              'src' => '312:19:9',
                            ],
                            1 =>
                            [
                              'attributes' =>
                              [
                                'constant' => false,
                                'name' => '_symbol',
                                'scope' => 1021,
                                'stateVariable' => false,
                                'storageLocation' => 'memory',
                                'type' => 'string',
                                'value' => null,
                                'visibility' => 'internal',
                              ],
                              'children' =>
                              [
                                0 =>
                                [
                                  'attributes' =>
                                  [
                                    'name' => 'string',
                                    'type' => 'string',
                                  ],
                                  'id' => 1009,
                                  'name' => 'ElementaryTypeName',
                                  'src' => '339:6:9',
                                ],
                              ],
                              'id' => 1010,
                              'name' => 'VariableDeclaration',
                              'src' => '339:21:9',
                            ],
                            2 =>
                            [
                              'attributes' =>
                              [
                                'constant' => false,
                                'name' => '_decimals',
                                'scope' => 1021,
                                'stateVariable' => false,
                                'storageLocation' => 'default',
                                'type' => 'uint256',
                                'value' => null,
                                'visibility' => 'internal',
                              ],
                              'children' =>
                              [
                                0 =>
                                [
                                  'attributes' =>
                                  [
                                    'name' => 'uint256',
                                    'type' => 'uint256',
                                  ],
                                  'id' => 1011,
                                  'name' => 'ElementaryTypeName',
                                  'src' => '368:7:9',
                                ],
                              ],
                              'id' => 1012,
                              'name' => 'VariableDeclaration',
                              'src' => '368:17:9',
                            ],
                          ],
                          'id' => 1013,
                          'name' => 'ParameterList',
                          'src' => '304:87:9',
                        ],
                        1 =>
                        [
                          'attributes' =>
                          [
                            'parameters' =>
                            [
                              0 => null,
                            ],
                          ],
                          'children' =>
                          [
                          ],
                          'id' => 1019,
                          'name' => 'ParameterList',
                          'src' => '445:0:9',
                        ],
                        2 =>
                        [
                          'children' =>
                          [
                            0 =>
                            [
                              'attributes' =>
                              [
                                'argumentTypes' => null,
                                'overloadedDeclarations' =>
                                [
                                  0 => null,
                                ],
                                'referencedDeclaration' => 898,
                                'type' => 'type(contract ERC20Detailed)',
                                'value' => 'ERC20Detailed',
                              ],
                              'id' => 1014,
                              'name' => 'Identifier',
                              'src' => '396:13:9',
                            ],
                            1 =>
                            [
                              'attributes' =>
                              [
                                'argumentTypes' => null,
                                'overloadedDeclarations' =>
                                [
                                  0 => null,
                                ],
                                'referencedDeclaration' => 1008,
                                'type' => 'string memory',
                                'value' => '_name',
                              ],
                              'id' => 1015,
                              'name' => 'Identifier',
                              'src' => '410:5:9',
                            ],
                            2 =>
                            [
                              'attributes' =>
                              [
                                'argumentTypes' => null,
                                'overloadedDeclarations' =>
                                [
                                  0 => null,
                                ],
                                'referencedDeclaration' => 1010,
                                'type' => 'string memory',
                                'value' => '_symbol',
                              ],
                              'id' => 1016,
                              'name' => 'Identifier',
                              'src' => '417:7:9',
                            ],
                            3 =>
                            [
                              'attributes' =>
                              [
                                'argumentTypes' => null,
                                'hexvalue' => '3138',
                                'isConstant' => false,
                                'isLValue' => false,
                                'isPure' => true,
                                'lValueRequested' => false,
                                'subdenomination' => null,
                                'token' => 'number',
                                'type' => 'int_const 18',
                                'value' => '18',
                              ],
                              'id' => 1017,
                              'name' => 'Literal',
                              'src' => '426:2:9',
                            ],
                          ],
                          'id' => 1018,
                          'name' => 'ModifierInvocation',
                          'src' => '396:33:9',
                        ],
                        3 =>
                        [
                          'attributes' =>
                          [
                            'statements' =>
                            [
                              0 => null,
                            ],
                          ],
                          'children' =>
                          [
                          ],
                          'id' => 1020,
                          'name' => 'Block',
                          'src' => '445:7:9',
                        ],
                      ],
                      'id' => 1021,
                      'name' => 'FunctionDefinition',
                      'src' => '293:159:9',
                    ],
                  ],
                  'id' => 1022,
                  'name' => 'ContractDefinition',
                  'src' => '219:235:9',
                ],
              ],
              'id' => 1023,
              'name' => 'SourceUnit',
              'src' => '0:455:9',
            ],
            'compiler' =>
            [
              'name' => 'solc',
              'version' => '0.5.17+commit.d19bba13.Emscripten.clang',
            ],
            'networks' =>
            [
              1239064198102255 =>
              [
                'events' =>
                [
                ],
                'links' =>
                [
                ],
                'address' => '0xB0281Dd5d779b94386973E65D561DB3b654C1b66',
                'transactionHash' => '0xd6c5e6a7fe342d5a8ccc3ae68c20b8ec4ab902962d3c0e70dc25044195ccedcf',
              ],
            ],
            'schemaVersion' => '3.4.3',
            'updatedAt' => '2021-09-13T16:26:44.318Z',
            'networkType' => 'ethereum',
            'devdoc' =>
            [
              'methods' =>
              [
                'allowance(address,address)' =>
                [
                  'details' => 'See {IERC20-allowance}.',
                ],
                'approve(address,uint256)' =>
                [
                  'details' => 'See {IERC20-approve}.     * Requirements:     * - `spender` cannot be the zero address.',
                ],
                'balanceOf(address)' =>
                [
                  'details' => 'See {IERC20-balanceOf}.',
                ],
                'burn(uint256)' =>
                [
                  'details' => 'Destroys `amount` tokens from the caller.     * See {ERC20-_burn}.',
                ],
                'burnFrom(address,uint256)' =>
                [
                  'details' => 'See {ERC20-_burnFrom}.',
                ],
                'decimals()' =>
                [
                  'details' => 'Returns the number of decimals used to get its user representation. For example, if `decimals` equals `2`, a balance of `505` tokens should be displayed to a user as `5,05` (`505 / 10 ** 2`).     * Tokens usually opt for a value of 18, imitating the relationship between Ether and Wei.     * NOTE: This information is only used for _display_ purposes: it in no way affects any of the arithmetic of the contract, including {IERC20-balanceOf} and {IERC20-transfer}.',
                ],
                'decreaseAllowance(address,uint256)' =>
                [
                  'details' => 'Atomically decreases the allowance granted to `spender` by the caller.     * This is an alternative to {approve} that can be used as a mitigation for problems described in {IERC20-approve}.     * Emits an {Approval} event indicating the updated allowance.     * Requirements:     * - `spender` cannot be the zero address. - `spender` must have allowance for the caller of at least `subtractedValue`.',
                ],
                'increaseAllowance(address,uint256)' =>
                [
                  'details' => 'Atomically increases the allowance granted to `spender` by the caller.     * This is an alternative to {approve} that can be used as a mitigation for problems described in {IERC20-approve}.     * Emits an {Approval} event indicating the updated allowance.     * Requirements:     * - `spender` cannot be the zero address.',
                ],
                'mint(address,uint256)' =>
                [
                  'details' => 'See {ERC20-_mint}.     * Requirements:     * - the caller must have the {MinterRole}.',
                ],
                'name()' =>
                [
                  'details' => 'Returns the name of the token.',
                ],
                'symbol()' =>
                [
                  'details' => 'Returns the symbol of the token, usually a shorter version of the name.',
                ],
                'totalSupply()' =>
                [
                  'details' => 'See {IERC20-totalSupply}.',
                ],
                'transfer(address,uint256)' =>
                [
                  'details' => 'See {IERC20-transfer}.     * Requirements:     * - `recipient` cannot be the zero address. - the caller must have a balance of at least `amount`.',
                ],
                'transferFrom(address,address,uint256)' =>
                [
                  'details' => 'See {IERC20-transferFrom}.     * Emits an {Approval} event indicating the updated allowance. This is not required by the EIP. See the note at the beginning of {ERC20};     * Requirements: - `sender` and `recipient` cannot be the zero address. - `sender` must have a balance of at least `amount`. - the caller must have allowance for `sender`\'s tokens of at least `amount`.',
                ],
              ],
            ],
            'userdoc' =>
            [
              'methods' =>
              [
              ],
            ],
        ];
    }
}
