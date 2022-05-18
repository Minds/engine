<?php

namespace Minds\Core\Security\TwoFactor\Store\Cassandra;

use Exception;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;
use Minds\Entities\User;

/**
 *
 */
class TwoFactorSecretRepository
{
    public function __construct(
        private ?CassandraClient $cassandraClient = null,
        private ?MindsConfig $mindsConfig = null,
        private ?User $user = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
        $this->mindsConfig ??= Di::_()->get('Config');
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $key
     * @return TwoFactorSecret|null
     * @throws Exception
     */
    public function get(string $key): ?TwoFactorSecret
    {
        $query = (new PreparedStatement())
            ->query(
                "SELECT *
                FROM
                    two_factor_codes
                WHERE
                    key = ?",
                [$key]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("No two factor secret found.");
        }

        $data = json_decode($response->first()['data'], true);

        return (new TwoFactorSecret())
            ->setGuid($data["_guid"])
            ->setTimestamp($data["ts"])
            ->setSecret($data["secret"]);
    }

    /**
     * @param string $key
     * @param string $secret
     * @return void
     * @throws Exception
     */
    public function add(string $key, string $secret): void
    {
        $query = (new PreparedStatement())
            ->query(
                "INSERT INTO
                two_factor_codes
                (
                    key,
                    data
                )
                VALUES
                    (?, ?)
                USING TTL ?;",
                [
                    $key,
                    $secret,
                    $this->getTTL()
                ]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("An error occurred while storing the 2factor code.");
        }
    }

    /**
     * @param string $key
     * @throws Exception
     */
    public function delete(string $key): void
    {
        $query = (new PreparedStatement())
            ->query(
                "DELETE FROM two_factor_codes WHERE key = ?",
                [$key]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("An error occurred while deleting stored 2factor code.");
        }
    }

    /**
     * Gets TTL for store. If not trusted, we are doing email confirmation, thus the ttl is 1 day.
     * For all other actions it is 15 minutes.
     * @return int - seconds for TTL.
     */
    private function getTTL(): int
    {
        return $this->user->isTrusted() ? $this->mindsConfig->get('two-factor-ttl')['trusted'] : $this->mindsConfig->get('two-factor-ttl')['untrusted'];
    }
}
