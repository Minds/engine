<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Types\Actor\PersonType;
use NotImplementedException;

class ActorFactory
{
    public static function build(array $json): AbstractActorType
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
