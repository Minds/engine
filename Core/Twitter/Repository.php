<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Cassandra\Bigint;
use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;
use Minds\Core\Twitter\Exceptions\TwitterDetailsNotFoundException;
use Minds\Core\Twitter\Models\TwitterDetails;

class Repository
{
    public function __construct(
        private ?Client $cassandraClient = null,
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param string $userGuid
     * @param string $accessToken
     * @param string $refreshToken
     * @return bool
     */
    public function storeOAuth2TokenInfo(
        string $userGuid,
        string $accessToken,
        string $accessTokenExpiry,
        string $refreshToken
    ): bool {
        $details = TwitterDetails::fromData([
            'user_guid' => $userGuid,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ]);

        $statement =
            "INSERT INTO twitter_sync
                (user_guid, access_token, access_token_expiry, refresh_token)
            VALUES
                (?, ?, ?, ?)";
        $values = [
            new Bigint($userGuid),
            $details->getAccessToken(),
            new Timestamp((int) $accessTokenExpiry, 0),
            $details->getRefreshToken()
        ];

        $query = (new PreparedStatement())->query($statement, $values);

        return (bool) $this->cassandraClient->request($query);
    }

    /**
     * @param string $userGuid
     * @return TwitterDetails
     * @throws TwitterDetailsNotFoundException
     */
    public function getDetails(string $userGuid): TwitterDetails
    {
        $statement = "SELECT * FROM twitter_sync WHERE user_guid = ?";
        $values = [ new Bigint($userGuid) ];
        $query = (new PreparedStatement())->query($statement, $values);

        $response = $this->cassandraClient->request($query);

        if (!$response || $response->count() === 0) {
            throw new TwitterDetailsNotFoundException();
        }

        return TwitterDetails::fromData($response->first());
    }
}
