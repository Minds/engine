<?php
namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Blockchain\Config;
use Minds\Core\Blockchain\Manager as BlockchainManager;
use Minds\Core\Blockchain\Services\Skale;
use Minds\Core\Di\Di;

class Manager
{
    public function __construct(
        protected ?Skale $skaleClient = null,
        protected ?BlockchainManager $manager = null
    ) {
        $this->skaleClient = $skaleClient ?? Di::_()->get('Blockchain\Services\Skale');
        $this->ethClient = $ethClient ?? Di::_()->get('Blockchain\Services\Ethereum');
        $this->manager = $manager ?? Di::_()->get('Blockchain\Manager');
    }

    public function canExit(string $receiver): bool
    {

        // $this->getCommunityPoolBalance($receiver);
        // SKALE config
        $skaleConfig = $this->manager->getPublicSettings()['skale'];
        
        // CommunityPool contract address
        $communityPoolAddress = $skaleConfig["skale_contracts_mainnet"]["community_pool_address"];

        // Derive schainHash by getting the Keccak256 hash of the SKALE chain name, and prepending 0x.
        $schainHash = '0x'.$this->ethClient->sha3($skaleConfig["chain_name"]);

        // eth_call via client, handles param encoding and call dispatch.
        $result = $this->ethClient->call($communityPoolAddress, 'checkUserBalance(bytes32,address)', [$schainHash, $receiver]);

        // TODO: Validate result
        return $result;
    }

    // working but makes me physically ill
    // public function getCommunityPoolBalance(string $receiver) {
    //     // SKALE config
    //     $skaleConfig = $this->manager->getPublicSettings()['skale'];
        
    //     // CommunityPool contract address
    //     $communityPoolAddress = $skaleConfig["skale_contracts_mainnet"]["community_pool_address"];

    //     $hex = implode(unpack("H*", $skaleConfig["chain_name"]));
        
    //     $result = $this->ethClient->call($communityPoolAddress, 'getBalance(address,string)', [
    //         $receiver,
    //         '0x0000000000000000000000000000000000000000000000000000000000000040000000000000000000000000000000000000000000000000000000000000000f676c616d6f726f75732d7379726d610000000000000000000000000000000000'
    //         // [type => 'glamorous-syrma', value => 'st'
    //     ]);

    //     return $result;
    // }

    public function getCommunityPoolBalance(string $receiver)
    {
        // SKALE config
        $skaleConfig = $this->manager->getPublicSettings()['skale'];
        
        // CommunityPool contract address
        $communityPoolAddress = $skaleConfig["skale_contracts_mainnet"]["community_pool_address"];
        
        $result = $this->ethClient->call($communityPoolAddress, 'getBalance(address,string)', [
            $receiver,
            [
                'value' => 'glamorous-syrma',
                'type' => 'string'
            ]
        ]);

        return hexdec($result);
    }
}
