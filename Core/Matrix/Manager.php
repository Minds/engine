<?php
/**
 * Matrix Application Manager
 */
namespace Minds\Core\Matrix;

use GuzzleHttp\Exception\RequestException;
use Minds\Core\Matrix\MatrixAccount;
use Minds\Entities\User;

class Manager
{
    /** @var Client */
    protected $client;

    /** @var MatrixConfig */
    protected $matrixConfig;

    public function __construct(Client $client = null, MatrixConfig $matrixConfig = null)
    {
        $this->client = $client ?? new Client();
        $this->matrixConfig = $matrixConfig ?? new MatrixConfig();
    }

    /**
     * Returns the matrix account of a user entity. If not found then one is created.
     * @param User $user
     * @return MatrixAccount
     */
    public function getAccountByUser(User $user): MatrixAccount
    {
        $matrixId = $this->getMatrixId($user);

        try {
            $response = $this->client->request('GET', '_synapse/admin/v2/users/' . $matrixId);
        } catch (RequestException $e) {
            // if 404 then create new account
            if ($e->getResponse()->getStatusCode() === 404) {
                return $this->createAccount($user);
            }

            throw $e; // Rethrow
        }

        $decodedResponse = json_decode($response->getBody(), true);

        $account = new MatrixAccount();
        $account->setId($decodedResponse['name'])
            ->setDeactivated((bool) $decodedResponse['deactivated'])
            ->setDisplayName($decodedResponse['displayname'])
            ->setAvatarUrl($decodedResponse['avatar_url'])
            ->setUserGuid($user->getGuid());

        return $account;
    }

    /**
     * Creates an account on matrix
     * @param User $user
     * @return MatrixAccount
     */
    public function createAccount(User $user): MatrixAccount
    {
        $matrixId = $this->getMatrixId($user);
    
        $payload = [
            "password" => base64_encode(openssl_random_pseudo_bytes(128)),
            "displayname" => $user->getName(),
            "avatar_url" => $user->getIconURL('master'),
            "admin" => false,
            "deactivated" => false
        ];

        $response = $this->client->request('PUT', '_synapse/admin/v2/users/' . $matrixId, [
            'json' => $payload,
        ]);

        $decodedResponse = json_decode($response->getBody(), true);

        $account = new MatrixAccount();
        $account->setId($decodedResponse['name'])
            ->setDeactivated((bool) $decodedResponse['deactivated'])
            ->setDisplayName($decodedResponse['displayname'])
            ->setAvatarUrl($decodedResponse['avatar_url']);

        return $account;
    }

    /**
     * Creates a temporary access token that allows the server to act on behalf
     * of the matrix account
     * @param User $user
     * @return string
     */
    public function getServerAccessToken(User $user): string
    {
        $matrixId = $this->getMatrixId($user);
        $response = $this->client->request('POST', "_synapse/admin/v1/users/$matrixId/login", [
            'valid_until_ms' => (time() + 300) * 1000, // Valid for 5 minutes
        ]);

        $decodedResponse = json_decode($response->getBody(), true);

        return $decodedResponse['access_token'];
    }

    /**
     * Return the SSO steps
     */
    public function completeSSO(User $user)
    {
    }

    public function getJoinedRooms(User $user)
    {
        $accessToken = $this->getServerAccessToken($user);
        $response = $this->client
            ->setAccessToken($accessToken)
            ->request('GET', '_matrix/client/r0/sync');
        echo (string) $response->getBody();
        exit;
    }

    /**
     * @param User $user
     * @return string
     */
    protected function getMatrixId(User $user): string
    {
        return "@{$user->getUsername()}:{$this->matrixConfig->getHomeserverDomain()}";
    }
}
