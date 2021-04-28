<?php
/**
 * Matrix Application Manager
 */
namespace Minds\Core\Matrix;

use GuzzleHttp\Exception\RequestException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Matrix\MatrixAccount;
use Minds\Entities\User;

class Manager
{
    /** @var Client */
    protected $client;

    /** @var MatrixConfig */
    protected $matrixConfig;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var array */
    protected $state = [];

    public function __construct(Client $client = null, MatrixConfig $matrixConfig = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->client = $client ?? new Client();
        $this->matrixConfig = $matrixConfig ?? new MatrixConfig();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Returns the matrix account of a user entity.
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
            // if ($e->getResponse()->getStatusCode() === 404) {
            //     return $this->createAccount($user);
            // }

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
     * Syncs a minds account to Matrix
     * @param User $user
     * @return MatrixAccount
     */
    public function syncAccount(User $user): MatrixAccount
    {
        $matrixId = $this->getMatrixId($user);

        // First fetch the current account
        $account = $this->getAccountByUser($user);

        $payload = [
            "displayname" => $user->getName(),
            "admin" => false,
            "deactivated" => false
        ];

        /**
         * Copy an avatar, if we need to
         */
        if ($avatarUrl = $this->copyAvatar($user)) {
            $payload['avatar_url'] = $avatarUrl;
        }

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
     * Will create a room between two users
     * @param User $user
     * @param User $reveiver
     * @return MatrixRoom
     */
    public function createDirectRoom(User $sender, User $receiver): MatrixRoom
    {
        $senderMatrixId = $this->getMatrixId($sender);
        $receiverMatrixId = $this->getMatrixId($receiver);
        // First, check to see that we don't already have a direct room

        $directRooms = $this->getDirectRooms($sender);

        /** @var MatrixRoom[] */
        $rooms = array_values(array_filter($directRooms, function ($room) use ($receiverMatrixId) {
            return $room->isDirectMessage() && in_array($receiverMatrixId, $room->getMembers(), false);
        }));

        if (count($rooms)) {
            return $rooms[0];
        }

        $endpoint = '_matrix/client/r0/createRoom';

        $response = $this->client
            ->setAccessToken($this->getServerAccessToken($sender))
            ->request('POST', $endpoint, [
                'json' => [
                    'is_direct' => true,
                    'visibility' => 'private',
                    'invite' => [ $receiverMatrixId ],
                    'preset' => 'trusted_private_chat',
                ]
            ]);

        $decodedResponse = json_decode($response->getBody(), true);

        $matrixRoom = new MatrixRoom();
        $matrixRoom->setName($receiver->getName())
                ->setId($decodedResponse['room_id'])
                ->setLastEvent(time())
                ->setMembers([$receiverMatrixId])
                ->setDirectMessage(true);

        /**
         * Patch for synapse - create a DM doesn't add to the 'people' section
         */

        $directRooms[] = $matrixRoom;
        $patchedDirectRooms = [];
    
        foreach ($directRooms as $room) {
            $member = $room->getMembers()[0];
            $patchedDirectRooms[$member] = [ $room->getId() ];
        }

        // Send PUT request to tag as a direct room
        $this->client
            ->setAccessToken($this->getServerAccessToken($sender))
            ->request('PUT', "_matrix/client/r0/user/$senderMatrixId/account_data/m.direct", [
                'json' => $patchedDirectRooms
            ]);

        return $matrixRoom;
    }

    /**
     * Return a list of direct rooms (does not include last timestamps)
     * @param User $user
     * @return MatrixRoom[]
     */
    protected function getDirectRooms(User $user)
    {
        try {
            $matrixId = $this->getMatrixId($user);
            $response = $this->client
            ->setAccessToken($this->getServerAccessToken($user))
            ->request('GET', "_matrix/client/r0/user/$matrixId/account_data/m.direct");

            $decodedResponse = json_decode($response->getBody(), true);

            /** @var MatrixRoom[] */
            $rooms = [];
            foreach ($decodedResponse as $memberId => $roomIds) {
                $matrixRoom = new MatrixRoom();
                $matrixRoom->setId($roomIds[0])
                ->setInvite(false)
                ->setMembers([$memberId])
                ->setDirectMessage(true);
                $rooms[] = $matrixRoom;
            }
        

            return $rooms;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return [];
            }
            throw $e;
        }
    }

    /**
     * Return a list of joined room
     * @param User $user
     * @return MatrixRoom[]
     */
    public function getJoinedRooms(User $user)
    {
        $matrixId = $this->getMatrixId($user);
       
        $data = $this->getState($user);
    
        /** @var MatrixRoom[] */
        $rooms = [];

        foreach ($data['rooms']['join'] as $roomId => $roomData) {
            $matrixRoom = new MatrixRoom();
            $matrixRoom->setId($roomId);
            $matrixRoom->setInvite(false);

            $this->getRoomFromStateEvents($roomData['state']['events'], $matrixRoom, $matrixId);

            $matrixRoom->setUnreadCount($roomData['unread_notifications']['notification_count']);

            if (count($roomData['timeline']['events'])) {
                $matrixRoom->setLastEvent(round($roomData['timeline']['events'][0]['origin_server_ts'] / 1000));
            }

            foreach ($data['account_data']['events'] as $event) {
                if ($event['type'] === 'm.direct') {
                    foreach ($event['content'] as $roomIds) {
                        if (in_array($roomId, $roomIds, true)) {
                            $matrixRoom->setDirectMessage(true);
                        }
                    }
                }
            }

            $rooms[] = $matrixRoom;
        }
        
        foreach ($data['rooms']['invite'] as $roomId => $roomData) {
            $matrixRoom = new MatrixRoom();
            $matrixRoom->setId($roomId);
            $matrixRoom->setInvite(true);
            
            $this->getRoomFromStateEvents($roomData['invite_state']['events'], $matrixRoom, $matrixId);
            
            $rooms[] = $matrixRoom;
        }

        usort($rooms, function ($a, $b) {
            return $a->getLastEvent() < $b->getLastEvent();
        });

        return $rooms;
    }

    /**
     * @param User $user
     * @return array
     */
    public function getState(User $user, $refresh = false): array
    {
        if ($this->state && !$refresh) {
            return $this->state;
        }

        $filters = json_encode([
            'room' => [
                'timeline' => [
                    'limit' => 1,
                    'types' => ['m.room.message', 'm.room.encrypted']
                ],
                'account_data' => [
                    'not_types' => ['*']
                ],
                'ephemeral' => [
                    'not_types' => ['*']
                ],
                'state' => [
                    'lazy_load_members' => true,
                ]
            ],
            'presence' => [
                'not_types' => ['m.presence']
            ],
            'account_data' => [
                'types' => ['im.vector.setting.breadcrumbs', 'm.direct']
            ],
        ]);

        $accessToken = $this->getServerAccessToken($user);
        $response = $this->client
            ->setAccessToken($accessToken)
            ->request('GET', '_matrix/client/r0/sync?filter=' . $filters);

        return $this->state = json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Returns an iterator of all accounts on the synapse server
     * @return iterable
     */
    public function getAccounts(): iterable
    {
        $from = 0;
        $limit = 100;

        while (true) {
            $endpoint = '_synapse/admin/v2/users';
            $params = http_build_query([ 'from' => $from, 'limit' => $limit, 'guests' => 'false' ]);
        
            $response = $this->client->request('GET', "$endpoint?$params");
            $decodedResponse = json_decode($response->getBody()->getContents(), true);

            foreach ($decodedResponse['users'] as $user) {
                $account = new MatrixAccount();
                $account->setId($user['name'])
                    ->setDeactivated((bool) $user['deactivated'])
                    ->setDisplayName($user['displayname'])
                    ->setAvatarUrl($user['avatar_url']);

                $username = ltrim(explode(':', $user['name'])[0], '@');
                $user = $this->entitiesBuilder->getByUserByIndex($username);

                if (!$user) {
                    continue;
                }

                $account->setUserGuid($user->getGuid());
                
                yield $account;
            }

            $from = $decodedResponse['next_token'];
            if (!$from) {
                break;
            }
        }
    }

    /**
     * Builds a room based on historic state events
     * @param array $events
     * @param MatrixRoom $matrixRoom
     * @param string $matrixId - the id of the matrix user
     * @return MatrixRoom
     */
    protected function getRoomFromStateEvents(array $events, MatrixRoom $matrixRoom, string $matrixId): MatrixRoom
    {
        foreach (array_reverse($events) as $event) {
            if ($event['type'] === 'm.room.member' &&
                $event['content']['is_direct'] === true
            ) {
                $matrixRoom->setDirectMessage(true);
            }

            // For DM's
            if ($event['type'] === 'm.room.member' &&
                $event['state_key'] !== $matrixId
            ) {
                $matrixRoom->setName($event['content']['displayname']);
                $matrixRoom->setAvatarUrl($event['content']['avatar_url']);
                $matrixRoom->setMembers([$event['state_key']]);
            }

            // For multi party rooms
            if ($event['type'] === 'm.room.name') {
                $matrixRoom->setName($event['content']['name']);
            }

            if ($events['type'] === 'm.room.canonical_alias') {
                $matrixRoom->setName($event['content']['alias']);
            }

            if ($event['type'] === 'm.room.avatar') {
                $matrixRoom->setAvatarUrl($event['content']['url']);
            }

            if ($event['origin_server_ts']) {
                $matrixRoom->setLastEvent(round($event['origin_server_ts'] / 1000));
            }
        }

        return $matrixRoom;
    }

    /**
     * Copies the minds avatae to matrix and returns the path
     * If we have a newer avatar on matrix we will not copy
     * @param User $user
     * @return string
     */
    protected function copyAvatar(User $user): ?string
    {
        $filename = $user->getGuid() . '-avatar.jpeg';
        $iconTime = $user->icontime;

        foreach ($this->getUserMediaList($user) as $media) {
            if ($media['upload_name'] === $filename &&
                $media['created_ts'] > $iconTime * 1000) {
                return null; // We will not copy a new avatar as a new one is already uploaded
            }
        }

        $userGuid = $user->getGuid();

        // Legacy users have short guids
        if ($user->legacy_guid) {
            $userGuid = $user->legacy_guid;
        }

        $file = new \ElggFile();
        $file->owner_guid = $userGuid;
        $file->setFilename("profile/{$userGuid}master.jpg");
        $file->open("read");

        $contents = $file->read();

        $accessToken = $this->getServerAccessToken($user);
        
        $endpoint = "_matrix/media/r0/upload?filename=$filename";

        try {
            $response = $this->client
            ->setAccessToken($accessToken)
            ->request('POST', $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'image/jpeg'
                ],
                'body' => $contents,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);


            return $data['content_uri'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Returns a list of all the users media
     * TODO: make this paginate
     * @param User $user
     * @return iterable
     */
    protected function getUserMediaList(User $user): iterable
    {
        $matrixId = $this->getMatrixId($user);

        $endpoint = "_synapse/admin/v1/users/$matrixId/media";

        $response = $this->client->request('GET', $endpoint);
        $contents = json_decode($response->getBody()->getContents(), true);
        foreach ($contents['media'] as $media) {
            yield $media;
        }
    }

    /**
     * Creates a temporary access token that allows the server to act on behalf
     * of the matrix account
     * @param User $user
     * @return string
     */
    protected function getServerAccessToken(User $user): string
    {
        $matrixId = $this->getMatrixId($user);
        $response = $this->client->request('POST', "_synapse/admin/v1/users/$matrixId/login", [
            'valid_until_ms' => (time() + 300) * 1000, // Valid for 5 minutes
        ]);

        $decodedResponse = json_decode($response->getBody(), true);

        return $decodedResponse['access_token'];
    }

    /**
     * @param User $user
     * @return string
     */
    protected function getMatrixId(User $user): string
    {
        $username = strtolower($user->getUsername());
        return "@{$username}:{$this->matrixConfig->getHomeserverDomain()}";
    }
}
