<?php declare(strict_types=1);

namespace Minds\Interfaces;

/**
 * The basic interface for creating a client/server Activity for Posting articles via the PubSub spec.
 * See: https://w3c.github.io/activitypub/#client-to-server-interactions
 */
interface ActivityPubClient
{
    public function setActivityPubServer(string $serverURL);

    public function setActor(string $actorName, string $actorURI);

    /**
     * See: https://w3c.github.io/activitypub/#create-activity-outbox
     * @param $to string[]
     * @param $cc string[]
     */
    public function postArticle(string $title, string $body, array $to, ?array $cc = null);

    /**
     * See: https://w3c.github.io/activitypub/#create-activity-outbox
     */
    public function like(string $refObjectURI, array $to, ?string $sumary = null, ?array $cc = null);
}
