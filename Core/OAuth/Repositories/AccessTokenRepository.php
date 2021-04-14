<?php
/**
 * Minds OAuth AccessTokenRepository.
 */

namespace Minds\Core\OAuth\Repositories;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Cassandra\Set;
use Cassandra\Type;
use Cassandra\Timestamp;
use Cassandra\Varint;
use DateTimeImmutable;
use Minds\Common\IpAddress;
use Zend\Diactoros\ServerRequestFactory;
use Minds\Common\Repository\Response;
use Minds\Core\OAuth\Entities\ClientEntity;
use Minds\Core\Sessions\CommonSessions\CommonSession;
use Minds\Entities\User;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /** @var Client $client */
    private $client;

    /** @var IpAddress */
    protected $ipAddress;

    public function __construct($client = null, $ipAddress = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
        $this->ipAddress = $ipAddress ?? new IpAddress();
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $ip = $this->ipAddress->get();

        $scopes = new Set(Type::text());
        foreach ($accessTokenEntity->getScopes() as $scope) {
            $scopes->add($scope->getIdentifier());
        }
        $prepared = new Prepared();
        $prepared->query('
            INSERT INTO oauth_access_tokens (token_id, client_id, user_id, expires, last_active, scopes, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ', [
                $accessTokenEntity->getIdentifier(),
                $accessTokenEntity->getClient()->getIdentifier(),
                new Varint($accessTokenEntity->getUserIdentifier()),
                new Timestamp($accessTokenEntity->getExpiryDateTime()->getTimestamp(), 0),
                new Timestamp(time(), 0), //now
                $scopes,
                $ip
            ]);

        $this->client->request($prepared);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId)
    {
        $prepared = new Prepared();
        $prepared->query('DELETE FROM oauth_access_tokens where token_id = ?', [
            $tokenId,
        ]);
        $this->client->request($prepared);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $prepared = new Prepared();
        $prepared->query('SELECT * FROM oauth_access_tokens where token_id = ?', [
            $tokenId,
        ]);
        $this->client->request($prepared);
        $response = $this->client->request($prepared);

        if (!$response || $response[0]['token_id'] != $tokenId) {
            return true; // Access token could not be found
        }

        return false; // Access token still exists
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }

    /**
     * {@inheritdoc}
     * @param string $userId
     * @return AccessTokenEntity[]
     */
    public function getList(string $userId): array
    {
        $prepared = new Prepared();
        $prepared->query("SELECT * FROM oauth_access_tokens WHERE user_id = ?", [
            new Varint($userId),
        ]);

        $rows = $this->client->request($prepared);

        if (!$rows) {
            return [];
        }

        foreach ($rows as $row) {
            $accessToken = new AccessTokenEntity();
            $accessToken->setIp($row['ip'] ?? '');
            $accessToken->setIdentifier($row['token_id']);
            $client = new ClientEntity();
            $client->setIdentifier($row['client_id']);
            $accessToken->setExpiryDateTime((new DateTimeImmutable)->setTimestamp($row['expires']->time()));
            $accessToken->setClient($client);
            $accessToken->setLastActive((int) $row['last_active']->time());
            $accessToken->setUserIdentifier((string) $row['user_id']);

            $accessTokens[] = $accessToken;
        }

        return $accessTokens;
    }
}
