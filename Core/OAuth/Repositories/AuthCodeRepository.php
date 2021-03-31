<?php
/**
 * Minds OAuth AuthCodeRepository
 */
namespace Minds\Core\OAuth\Repositories;

use Cassandra\Bigint;
use Cassandra\Timestamp;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Minds\Core\Di\Di;
use Minds\Core\OAuth\Entities\AuthCodeEntity;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /** @var Client $client */
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $prepared = new Prepared;
        $prepared->query("
            INSERT INTO oauth_auth_codes (code_id, client_id, user_id, expires)
            VALUES (?, ?, ?, ?)
            USING TTL ?
            ", [
                $authCodeEntity->getIdentifier(),
                $authCodeEntity->getClient()->getIdentifier(),
                new Bigint($authCodeEntity->getUserIdentifier()),
                new Timestamp($authCodeEntity->getExpiryDateTime()->getTimestamp(), 0),
                $authCodeEntity->getExpiryDateTime()->getTimestamp() - time(),
            ]);
        $this->client->request($prepared);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId)
    {
        $prepared = new Prepared;
        $prepared->query("
            DELETE FROM oauth_auth_codes WHERE code_id = ?
            ", [
                $codeId,
            ]);
        $this->client->request($prepared);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        $prepared = new Prepared;
        $prepared->query("SELECT * FROM oauth_auth_codes where code_id = ?", [
            $codeId
        ]);

        $this->client->request($prepared);
        $response = $this->client->request($prepared);

        if (!isset($response[0])) {
            return true; // Code not found
        }

        return false; // Code exists
    }

    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }
}
