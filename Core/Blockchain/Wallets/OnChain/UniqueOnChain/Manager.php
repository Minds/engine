<?php
namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Common\Repository\Response;
use Minds\Exceptions\UserErrorException;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Ethereum */
    protected $ethereum;

    /** @var int */
    const UNIX_TS_TOLERANCE = 300; // Allow clocks to be 5 minutes slow

    public function __construct(Repository $repository = null, Ethereum $ethereum = null)
    {
        $this->repository = $repository ?? new Repository();
        $this->ethereum = $ethereum ?? Di::_()->get('Blockchain\Services\Ethereum');
    }

    /**
     * Adds an address. Will overwrite is another exists
     * @param UniqueOnChainAddress $address
     * @return bool
     */
    public function add(UniqueOnChainAddress $address): bool
    {
        // Confirm the signature is correct

        $signedAddress =  $this->ethereum->verifyMessage($address->getPayload(), $address->getSignature());

        if ($signedAddress !== $address->getAddress()) {
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

        return $this->repository->add($address);
    }

    /**
     * Adds an address. Will overwrite is another exists
     * @param UniqueOnChainAddress $address
     * @return bool
     */
    public function delete(UniqueOnChainAddress $address): bool
    {
        $found = $this->getByAddress($address->getAddress());
        if (!$found || $found->getUserGuid() !== $address->getUserGuid()) {
            return false;
        }
        return $this->repository->delete($address);
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
     * @return UniqueOnChainAddress[]
     */
    public function getAll(): Response
    {
        return $this->repository->getList([]);
    }
}
