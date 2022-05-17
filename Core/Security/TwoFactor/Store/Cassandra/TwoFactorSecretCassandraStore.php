<?php

namespace Minds\Core\Security\TwoFactor\Store\Cassandra;

use Exception;
use Minds\Core\Security\TwoFactor\Store\AbstractTwoFactorSecretStore;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;
use Minds\Core\Security\TwoFactor\Store\TwoFactoSecretStoreInterface;
use Minds\Entities\User;

/**
 * @inheritDoc
 */
class TwoFactorSecretCassandraStore extends AbstractTwoFactorSecretStore
{
    public function __construct(
        private ?TwoFactorSecretRepository $repository = null
    ) {
        $this->repository ??= new TwoFactorSecretRepository();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function get(User $user): ?TwoFactorSecret
    {
        return $this->getByKey($this->getKey($user));
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getByKey(string $key): ?TwoFactorSecret
    {
        return $this->repository->get($key);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function set(User $user, string $secret): string
    {
        $key = $this->getKey($user);

        $secretJson = json_encode(
            (new TwoFactorSecret())
                ->setGuid($user->guid)
                ->setTimestamp(time())
                ->setSecret($secret)
        );

        $this->repository->add($key, $secretJson);

        return $key;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function delete(string $key): TwoFactoSecretStoreInterface
    {
        $this->repository->delete($key);
        return $this;
    }
}
