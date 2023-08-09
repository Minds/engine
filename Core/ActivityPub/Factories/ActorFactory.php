<?php
namespace Minds\Core\ActivityPub\Factories;

use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Actor\ApplicationType;
use Minds\Core\ActivityPub\Types\Actor\GroupType;
use Minds\Core\ActivityPub\Types\Actor\OrganizationType;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\ActivityPub\Types\Actor\PublicKeyType;
use Minds\Core\ActivityPub\Types\Actor\ServiceType;
use Minds\Core\Config\Config;
use Minds\Core\Webfinger;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;

class ActorFactory
{
    public function __construct(
        protected Manager $manager,
        protected Client $client,
        protected Webfinger\Manager $webfingerManager,
        protected Config $config
    ) {
    }

    /**
     * Builds an actor from their webfinger resource. Pass a username like `mark@minds.com`
     */
    public function fromWebfinger(string $username): AbstractActorType
    {
        $json = $this->webfingerManager->get('acct:' . $username);

        foreach ($json['links'] as $link) {
            if ($link['rel'] === 'self') {
                $uri = $link['href'];
                return $this->fromuri($uri);
            }
        }
       
        throw new NotFoundException();
    }

    /**
     * Builds Actor from a uri.
     */
    public function fromUri(string $uri): AbstractActorType
    {
        // TODO: is this a local entity?

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
                'url' => $entity->getIconURL('large'),
            ],
            'publicKey' => [
                'id' => $id . '#main-key',
                'owner' =>  $id,
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
        $actor = match($json['type']) {
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
}
