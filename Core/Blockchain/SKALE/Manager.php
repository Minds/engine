<?php
namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Blockchain\Services\MindsWeb3Service;
use Minds\Core\Blockchain\Services\Web3Services\MindsSKALEWeb3Service;
use Minds\Core\Blockchain\SKALE\Faucet\FaucetLimiter;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * SKALE manager - handles SKALE related tasks.
 * @package Minds\Core\Blockchain\SKALE
 */
class Manager
{
    /**
     * SKALE manager constructor.
     * @param MindsSKALEWeb3Service|null $skaleWeb3Service - web3 service.
     * @param FaucetLimiter|null $faucetLimiter - rate limiter for faucet.
     * @param Config|null $config - global config.
     */
    public function __construct(
        protected ?MindsSKALEWeb3Service $skaleWeb3Service = null,
        protected ?FaucetLimiter $faucetLimiter = null,
        protected ?Config $config = null
    ) {
        $this->skaleWeb3Service = $skaleWeb3Service ?? Di::_()->get('Blockchain\Services\MindsSkaleWeb3');
        $this->faucetLimiter = $faucetLimiter ?? Di::_()->get('Blockchain\SKALE\FaucetLimiter');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Request skETH from the Minds SKALE faucet.
     * @param User $user - user to request for.
     * @param string $address - address to request for.
     * @throws RateLimitException - when rate limits are exceeded.
     * @throws UserErrorException - address-less request.
     * @throws ServerErrorException - internal error.
     * @return string - tx hash.
     */
    public function requestFromFaucet(User $user, string $address = null): string
    {
        if (!$address) {
            if (!$user->getEthWallet()) {
                throw new UserErrorException('No address provided');
            }
            $address = $user->getEthWallet();
        }

        // Can throw RateLimitException.
        $this->faucetLimiter->checkAndIncrement($user, $address);

        $txHash = $this->skaleWeb3Service->requestFromSKETHFaucet($address);

        return $txHash;
    }
}
