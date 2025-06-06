<?php
namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Blockchain\OnchainBalances\OnchainBalancesService;
use Minds\Core\Blockchain\Util;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Rewards\Restrictions\Blockchain\Manager as RestrictionsManager;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Ethereum */
    protected $ethereum;

    /** @var int */
    const UNIX_TS_TOLERANCE = 300; // Allow clocks to be 5 minutes slow

    public function __construct(
        Repository $repository = null,
        Ethereum $ethereum = null,
        private ?OnchainBalancesService $onchainBalancesService = null,
        private ?RestrictionsManager $restrictionsManager = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->ethereum = $ethereum ?? Di::_()->get('Blockchain\Services\Ethereum');
        $this->onchainBalancesService ??= Di::_()->get(OnchainBalancesService::class);
        $this->restrictionsManager ??= Di::_()->get('Rewards\Restrictions\Blockchain\Manager');
    }

    /**
     * Adds an address. Will overwrite is another exists
     * @param UniqueOnChainAddress $address
     * @return bool
     */
    public function add(UniqueOnChainAddress $address, User $user = null, bool $addAddress = false): bool
    {
        if ($user) {
            $this->restrictionsManager->gatekeeper($address->getAddress(), $user);
        }

        // Confirm the signature is correct

        $signedAddress =  $this->ethereum->verifyMessage($address->getPayload(), $address->getSignature());

        if (strtolower($signedAddress) !== strtolower($address->getAddress())) {
            throw new UserErrorException("Signature could not be verified");
        }

        $decodedPayload = json_decode($address->getPayload(), true);
        $tsDiff = time() - $decodedPayload['unix_ts'];
        if ($tsDiff > static::UNIX_TS_TOLERANCE || $tsDiff < static::UNIX_TS_TOLERANCE * -1) {
            throw new UserErrorException("Timestamp invalid ($tsDiff seconds). Please ensure your clock is correctly set");
        }

        if ((string) $decodedPayload['user_guid'] !== (string) $address->getUserGuid()) {
            throw new UserErrorException("Signed message user id does match your current user");
        }

        $added = $this->repository->add($address);

        if ($added && $user && $addAddress) {
            $user->setEthWallet($address->getAddress());
            
            (new Save())->setEntity($user)->withMutatedAttributes(['eth_wallet'])->save();
        }

        return $added;
    }

    /**
     * Adds an address. Will overwrite is another exists
     * @param UniqueOnChainAddress $address
     * @param bool $removeAddress
     * @return bool
     */
    public function delete(UniqueOnChainAddress $address, User $user = null, $removeAddress = false): bool
    {
        $found = $this->getByAddress($address->getAddress());
        if (!$found || $found->getUserGuid() !== $address->getUserGuid()) {
            return false;
        }

        $removed = $this->repository->delete($address);

        if ($removeAddress && $user && $removed) {
            $user->setEthWallet('');
            (new Save())->setEntity($user)->withMutatedAttributes(['eth_wallet'])->save();
        }

        return $removed;
    }

    /**
     * Confirms if unique and verified
     * @param User $user
     * @return bool
     */
    public function isUnique(User $user): bool
    {
        $uniqueOnChain = $this->repository->get($user->getEthWallet());
        if (!$uniqueOnChain) {
            return false;
        }
        return $uniqueOnChain->getUserGuid() === (string) $user->getGuid();
    }

    /**
     * Returns UniqueOnChain by address, null if not found
     * @param string $address
     * @return UniqueOnChainAddress
     */
    public function getByAddress(string $address): ?UniqueOnChainAddress
    {
        return $this->repository->get($address);
    }

    /**
     * Gets all unique onchain addresses.
     * @return iterable<UniqueOnChainAddress>
     */
    public function getAll(int $asOf = null): iterable
    {
        $emittedAddresses = [];

        foreach ([Util::BASE_CHAIN_ID, Util::ETHEREUM_CHAIN_ID] as $chainId) {
            foreach ($this->onchainBalancesService->getAll(asOf: $asOf, chainId: $chainId) as $account) {
                if (isset($emittedAddresses[$account['id']])) {
                    continue;
                } else {
                    $emittedAddresses[$account['id']] = true;
                }
                
                $uniqueOnchainAddress = $this->repository->get($account['id']);
                if ($uniqueOnchainAddress) {
                    $uniqueOnchainAddress->setTokenBalance($account['balances'][0]['amount']);
                    yield $uniqueOnchainAddress;
                }
            }
        }
    }

}
