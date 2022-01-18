<?php
namespace Minds\Core\Blockchain\SKALE\CommunityPool;

use Minds\Core\Blockchain\Services\Web3Services\MindsSKALEMainnetService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * SKALE CommunityPool Manager.
 * @package Minds\Core\Blockchain\SKALE\CommunityPool
 */
class Manager
{
    /**
     * SKALE CommunityPool manager constructor.
     * @param MindsSKALEMainnetService|null $skaleWeb3MainnetService - web3 service.
     * @param Config|null $config - global config.
     */
    public function __construct(
        protected ?MindsSKALEMainnetService $skaleWeb3MainnetService = null,
        protected ?Config $config = null
    ) {
        $this->skaleWeb3MainnetService = $skaleWeb3Service ?? Di::_()->get('Blockchain\Services\MindsSKALEMainnetService');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Whether a user can exit from the SKALE chain based on a contract call determining they
     * have a high enough CommunityPool balance.
     * @param string $requester - the user we are checking.
     * @return bool - true if requester can exit.
     */
    public function canExit(string $requester): bool
    {
        return $this->skaleWeb3MainnetService->canExit($requester);
    }
}
