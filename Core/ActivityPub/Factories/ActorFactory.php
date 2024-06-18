<?php

namespace Minds\Core\ActivityPub\Factories;

use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Exceptions\NotImplementedException;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Actor\ApplicationType;
use Minds\Core\ActivityPub\Types\Actor\GroupType;
use Minds\Core\ActivityPub\Types\Actor\OrganizationType;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\ActivityPub\Types\Actor\PublicKeyType;
use Minds\Core\ActivityPub\Types\Actor\ServiceType;
use Minds\Core\ActivityPub\Types\Object\ImageType;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;

class ActorFactory
{
    public const ACTOR_TYPES = [
        'Person' => PersonType::class,
        'Application' => ApplicationType::class,
        'Group' => GroupType::class,
        'Organization' => OrganizationType::class,
        'Service' => ServiceType::class,
    ];

    public const MINDS_APPLICATION_PREFERRED_USERNAME = "application";

    public const MINDS_APPLICATION_ACTOR_GUID = 0;

    public function __construct(
        protected Manager              $manager,
        protected Client               $client,
        protected Config               $config,
        private readonly InMemoryCache $cache
    ) {
    }

    /**
     * Builds an actor from their webfinger resource. Pass a username like `mark@minds.com`
     */
    public function fromWebfinger(string $username): AbstractActorType
    {
        $uri = $this->manager->getUriFromUsername($username, revalidateWebfinger: true);

        if ($uri) {
            return $this->fromUri($uri);
        }

        throw new NotFoundException();
    }

    /**
     * Builds Actor from a uri.
     */
    public function fromUri(string $uri): AbstractActorType
    {
        if ($this->manager->isLocalUri($uri)) {
            $entity = $this->manager->getEntityFromUri($uri);
            if (!$entity) {
                throw new NotFoundException();
            }
            return $this->fromEntity($entity);
        }

        try {
            $response = $this->client->request('GET', $uri);
            $json = json_decode($response->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            throw new UserErrorException("Could not connect to $uri");
        }

        return $this->fromJson($json);
    }

    /**
     * Builds Actor from a local entity. Only supports Users for now.
     */
    public function fromEntity(EntityInterface $entity): AbstractActorType
    {
        // Build the json array from the entity
        if (!$entity instanceof User) {
            throw new NotImplementedException();
        }

        /**
         * If we are building a remote user, then use their uri
         */
        if ($uri = $this->manager->getUriFromEntity($entity)) {
            if (!$this->manager->isLocalUri($uri)) {
                return $this->fromUri($uri);
            }
        }

        $baseUrl = $this->config->get('site_url') . 'api/activitypub/';

        $id = $baseUrl . 'users/' . $entity->getGuid();

        $private = $this->manager->getPrivateKey($entity);
        $publicKey = $private->getPublicKey();

        $json = [
            'id' => $id,
            'type' => 'Person',
            'name' => $entity->getName(),
            'inbox' => $id . '/inbox',
            'outbox' => $id . '/outbox',
            'followers' => $id . '/followers',
            'following' => $id . '/following',
            'liked' => $id . '/liked',
            'preferredUsername' => $entity->getUsername(),
            'url' => $this->config->get('site_url') . $entity->getUsername(),
            'summary' => $entity->briefdescription,
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $entity->getIconURL('large'),
            ],
            'publicKey' => [
                'id' => $id . '#main-key',
                'owner' => $id,
                'publicKeyPem' => $publicKey,
            ],
            'endpoints' => [
                'sharedInbox' => $baseUrl . 'inbox'
            ],
        ];

        return $this->fromJson($json);
    }

    /**
     * Pass through an array of data
     */
    public function fromJson(array $json): AbstractActorType
    {
        return $this->build($json);
    }

    /**
     * Builds the ActorType from the provided data
     */
    protected function build(array $json): AbstractActorType
    {
        $actor = match ($json['type']) {
            'Person' => new PersonType(),
            'Application' => new ApplicationType(),
            'Group' => new GroupType(),
            'Organization' => new OrganizationType(),
            'Service' => new ServiceType(),
            default => throw new NotImplementedException()
        };

        // Must
        $actor->id = $json['id'];
        $actor->inbox = $json['inbox'];

        if (!isset($json['outbox'])) {
            throw new UserErrorException("Outbox must be provided");
        }

        $actor->outbox = $json['outbox'];

        // May
        if (isset($json['name'])) {
            $actor->name = $json['name'];
        }

        if ($json['publicKey']) {
            $actor->publicKey = new PublicKeyType(
                id: $json['publicKey']['id'],
                owner: $json['publicKey']['owner'],
                publicKeyPem: $json['publicKey']['publicKeyPem'],
            );
        }

        if (isset($json['preferredUsername'])) {
            $actor->preferredUsername = $json['preferredUsername'];
        }

        if (isset($json['url'])) {
            $actor->url = $json['url'];
        }

        if (isset($json['icon'])) {
            $icon = new ImageType();
            if (isset($json['icon']['mediaType'])) {
                $icon->mediaType = $json['icon']['mediaType'];
            }
            $icon->url = $json['icon']['url'] ?? '';
            $actor->icon = $icon;
        }

        if (isset($json['endpoints'])) {
            $actor->endpoints = $json['endpoints'];
        }

        switch (get_class($actor)) {
            case PersonType::class:
                $actor->following = $json['following'];
                $actor->followers = $json['followers'];

                if (isset($json['liked'])) {
                    $actor->liked = $json['liked'];
                }

                break;
        }

        return $actor;
    }

    public function buildMindsApplicationActor(): ApplicationType
    {
        $actor = new ApplicationType();
        $actor->id = $this->config->get('site_url') . 'api/activitypub/actor';
        $actor->preferredUsername = self::MINDS_APPLICATION_PREFERRED_USERNAME;
        $actor->url = $this->config->get('site_url');
        $actor->endpoints = [
            'sharedInbox' => $this->config->get('site_url') . 'api/activitypub/inbox'
        ];
        $actor->inbox = $actor->id . '/inbox';
        $actor->outbox = $actor->id . '/outbox';
        $actor->manuallyApprovesFollowers = true;

        $publicKey = $this->cache->get("activitypub:key:$actor->id");
        if (!$publicKey) {
            $publicKey = ($this->manager->getPrivateKeyByUserGuid(0))
                ->getPublicKey();

            $this->cache->set("activitypub:key:$actor->id", $publicKey);
        }

        $actor->publicKey = new PublicKeyType(
            id: $actor->id . '#main-key',
            owner: $actor->id,
            publicKeyPem: $publicKey
        );

        return $actor;
    }
}
