<?php

namespace Minds\Core\Nostr;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;

class Manager
{
    private Logger $logger;

    public function __construct(
        protected ?Config             $config = null,
        protected ?EntitiesBuilder    $entitiesBuilder = null,
        protected ?Keys               $keys = null,
        private ?Repository           $repository = null,
        private ?EntitiesResolver     $entitiesResolver = null,
        private ?ElasticSearchManager $elasticSearchManager = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->keys ??= new Keys();
        $this->repository ??= new Repository();
        $this->entitiesResolver ??= new EntitiesResolver();
        $this->elasticSearchManager ??= Di::_()->get("Feeds\Elastic\Manager");
        $this->logger = Di::_()->get("Logger");
    }

    /**
     * Begins MySQL transaction
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->repository->beginTransaction();
    }

    /**
     * Commits MySQL transactions
     * @return bool
     */
    public function commit(): bool
    {
        return $this->repository->commit();
    }

    /**
     * Roll back MySQL transactions
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->repository->rollBack();
    }

    /**
     * Ask a Minds developer if you want to be added to this list.
     * @param string $pubKey
     * @return bool
     */
    public function addToWhitelist(string $pubKey): bool
    {
        return $this->repository->addToWhitelist($pubKey);
    }

    /**
     * Whitelist of who is allowed to post from nostr to Minds
     * Ask a Minds developer if you want to be added to this list.
     * @param string $pubKey
     * @return bool
     */
    public function isOnWhitelist(string $pubKey): bool
    {
        return $this->repository->isOnWhitelist($pubKey);
    }

    /**
     * @param string $username
     * @return string - Returns a base32 public key
     */
    public function getPublicKeyFromUsername(string $username): string
    {
        $user = $this->entitiesBuilder->getByUserByIndex($username);

        if (!$user) {
            throw new NotFoundException("User with username '$username' not found");
        }

        return $this->getPublicKeyFromUser($user);
    }

    /**
     * @param string $username
     * @return string - Returns a base32 public key
     */
    public function getPublicKeyFromUser(User $user): string
    {
        $publicKey = $this->keys->withUser($user)->getSecp256k1PublicKey();

        // MH: lets index on the fly so we don't need to rely on reverse indexing job
        $this->repository->addNostrUser($user, $publicKey);

        return $publicKey;
    }

    /**
     * Will return a Minds Users from a nostr public key
     * @param string $pubKey
     * @return User|null
     */
    public function getUserByPublicKey(string $pubKey): ?User
    {
        return $this->repository->getUserFromNostrPublicKey($pubKey);
    }

    /**
     * Will build a signed Nostr event
     * https://github.com/nostr-protocol/nips/blob/master/01.md#events-and-signatures
     * @param EntityInterface $entity
     * @return NostrEvent
     */
    public function buildNostrEvent(EntityInterface $entity): NostrEvent
    {
        $owner = ($entity instanceof User) ? $entity : $this->entitiesBuilder->single($entity->getOwnerGuid());
        if (!$owner instanceof User) {
            throw new ServerErrorException("Entity with no owner passed. We can not sign this");
        }
        $publicKey = $this->keys->withUser($owner)->getSecp256k1PublicKey();

        $kind = 1; // Text_note
        $content = '';
        $createdAt = 0;

        // Want to use a switch but php spec doesn't pass through the correct class name
        // and we want to use instanceof...
        // switch (get_class($entity)) {

        if ($entity instanceof Activity) {
            /** @var Activity */
            $activity = $entity;
            $content = (string)$activity->getMessage();

            if (
                $activity->getEntityGuid()
                || $activity->isRemind()
                || $activity->isQuotedPost()
            ) {
                $content .= ' ' . $activity->getURL();
            }

            $createdAt = $activity->getTimeCreated();
        } elseif ($entity instanceof User) {
            /** @var User */
            $user = $entity;
            $kind = 0; // set_metadata
            $content = json_encode([
                'name' => $user->getUsername() . '@' . $this->getDomain(),
                'about' => (string)$user->briefdescription,
                'picture' => $user->getIconURL('medium'),
            ], JSON_UNESCAPED_SLASHES);
            // $createdAt = $user->getTimeCreated();
            $createdAt = (int) $user->icontime;
        } else {
            throw new ServerErrorException("Unsupported entity type " . get_class($entity));
        }

        $id = hash('sha256', json_encode([// sha256 hash
            0,
            strtolower($publicKey), // <pubkey, as a (lowercase) hex string>,
            $createdAt, //  <created_at, as a number>,
            $kind, // kind
            [], // <tags, as an array of arrays of strings>,
            $content, // <content, as a string>
        ], JSON_UNESCAPED_SLASHES));

        $ctx = secp256k1_context_create(SECP256K1_CONTEXT_SIGN);

        $schnorrKeypair = null;
        $sig64 = null;
        $auxRand = null;

        secp256k1_keypair_create($ctx, $schnorrKeypair, $this->keys->withUser($owner)->getSecp256k1PrivateKey());

        secp256k1_schnorrsig_sign($ctx, $sig64, pack('H*', $id), $schnorrKeypair, 'secp256k1_nonce_function_bip340', $auxRand);

        $nostrEvent = new NostrEvent();
        $nostrEvent->setId($id)
            ->setPubKey($publicKey)
            ->setCreated_at($createdAt)
            ->setKind($kind)
            ->setTags([])
            ->setContent($content)
            ->setSig(unpack("H*", (string)$sig64)[1]);

        return $nostrEvent;
    }


    /**
     * Verifies than an event is correctly signed
     * @param string $jsonPayload
     * @return bool
     */
    public function verifyEvent(string $jsonPayload): bool
    {
        $event = json_decode($jsonPayload, true);

        $ctx = secp256k1_context_create(SECP256K1_CONTEXT_VERIFY);

        secp256k1_xonly_pubkey_parse($ctx, $xonlyPubKey, pack('H*', $event[1]['pubkey']));

        $result = secp256k1_schnorrsig_verify(
            $ctx,
            pack('H*', $event[1]['sig']),
            pack('H*', $event[1]['id']),
            $xonlyPubKey
        );

        return (bool)$result;
    }

    /**
     * @return string
     */
    protected function getDomain(): string
    {
        return urlencode($this->config->get('nostr')['domain'] ?? '');
    }

    /**
     * Returns events for Minds 'custodial' nostr accounts. ie. Minds channels, not source=nostr
     * @param array $filters
     * @return NostrEvent[]
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws Exception
     */
    public function getElasticNostrEvents(array $filters, int $limit): array
    {

        // If we're requesting kind 0 without specifying "authors", then query for internal pubkeys
        if (
            in_array(0, $filters['kinds'], true) &&
                count($filters['authors']) == 0
        ) {
            $filters['authors'] = $this->repository->getInternalPublicKeys($limit);
        }

        $userGuids = [];
        $events = [];

        if ($filters['authors']) {
            /**
             * @var User $user
             */
            foreach ($this->repository->getUserFromNostrPublicKeys($filters['authors']) as $user) {
                if ($user && $user->getSource() !== 'nostr') {
                    $userGuids[] = $user->getGuid();
                    if (in_array(0, $filters['kinds'], true)) {
                        $events[] = $this->buildNostrEvent($user);
                    }
                }
            }

            // If we request authors and don't find any internal users for the pubkeys, return
            if (count($userGuids) == 0) {
                return $events;
            }
        }

        $opts = [
            'container_guid' => $userGuids,
            'period' => 'all',
            'algorithm' => 'latest',
            'type' => 'activity',
            'limit' => $limit,
            'single_owner_threshold' => 0,
            'access_id' => 2,
            'as_activities' => true
        ];

        // If we have since and not until
        if ($filters['since'] && !$filters['until']) {
            // from_timestamp == lower bound
            $opts['from_timestamp'] = $filters['since'] * 1000;
            $opts['reverse_sort'] = true;
        } elseif (!$filters['since'] && $filters['until']) {
            // from_timestamp == upper bound
            $opts['from_timestamp'] = $filters['until'] * 1000;
        } elseif ($filters['since'] && $filters['until']) {
            // to_timestamp == lower bound && from_timstamp == upper bound
            $opts['to_timestamp'] = $filters['since'] * 1000;
            $opts['from_timestamp'] = $filters['until'] * 1000;
        }

        if (in_array(1, $filters['kinds'], true)) {
            $activities = $this->elasticSearchManager->getList($opts);

            /**
             * @var FeedSyncEntity $activity
             */
            foreach ($activities as $activity) {
                if ($activity->getEntity()) {
                    $events[] = $this->buildNostrEvent($activity->getEntity());
                }
            }
        }

        return $events;
    }

    /**
     * Add raw Nostr Events to our database
     * @param NostrEvent $nostrEvent
     * @return bool
     */
    public function addEvent(NostrEvent $nostrEvent): bool
    {
        return $this->repository->addEvent($nostrEvent);
    }

    /**
     * Adds reply for a given nostr event
     * @param string $eventId
     * @param array $tag
     * @return bool
     */
    public function addReply(string $eventId, array $tag): bool
    {
        return $this->repository->addReply($eventId, $tag);
    }

    /**
     * Adds mention for a given nostr event
     * @param string $eventId
     * @param array $tag
     * @return bool
     */
    public function addMention(string $eventId, array $tag): bool
    {
        return $this->repository->addMention($eventId, $tag);
    }

    /**
     * Pair a Minds User with a Nostr public key
     * @param User $user
     * @param string $nostrPublicKey
     * @return bool
     */
    public function addNostrUser(User $user, string $nostrPublicKey): bool
    {
        return $this->repository->addNostrUser($user, $nostrPublicKey);
    }

    /**
     * Return Activity entity from a NostrId
     * @param string $nostrId
     * @return Activity
     */
    public function getActivityFromNostrId($nostrId): ?EntityInterface
    {
        return $this->repository->getActivityFromNostrId($nostrId);
    }

    /**
     * Effectively indexes a Minds Activity posts to a Nostr ID
     * @param Activity $activity
     * @param string $nostrId
     * @return bool
     */
    public function addActivityToNostrId(Activity $activity, string $nostrId): bool
    {
        return $this->repository->addActivityToNostrId($activity, $nostrId);
    }

    /**
     * Returns NostrEvents from filters
     * @param array $filters
     * @return iterable<NostrEvent>
     */
    public function getNostrEvents(array $filters = []): iterable
    {
        return $this->repository->getEvents($filters);
    }
}
