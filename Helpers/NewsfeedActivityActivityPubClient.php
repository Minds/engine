<?php declare(strict_types=1);

namespace Minds\Helpers;

use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Psr7\Response;
use LogicException;
use Minds\Core\Blogs\Blog;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Interfaces\ActivityPubClient;

class NewsfeedActivityActivityPubClient implements ActivityPubClient
{
    /** @var Config */
    protected $config;

    /** @var Guzzle_Client */
    protected $client;

    /** @var Response */
    public $response;

    /** @var string */
    protected $activityPubURI;

    /** @var string */
    protected $actorName;

    /** @var string */
    protected $actorURI;

    public function __construct(Guzzle_Client $client = null)
    {
        $this->config = Di::_()->get('Config');
        $this->client = $client ?? new Guzzle_Client();
    }

    public function setActivityPubServer(string $serverURL)
    {
        $this->activityPubURI = $serverURL;
    }

    public function setActor(string $actorName, string $actorURI)
    {
        $this->actorName = $actorName;
        $this->actorURI = $actorURI;
    }

    private function assertPubSubURI()
    {
        if (!$this->activityPubURI) {
            throw new LogicException('The PubSub URI has not been specified.');
        }
    }

    private function assertActor()
    {
        if (!$this->actorURI) {
            throw new LogicException('The PubSub actor has not been specified.');
        }
    }

    protected function validate()
    {
        $this->assertPubSubURI();
        $this->assertActor();
    }

    /**
     * See: https://w3c.github.io/activitypub/#create-activity-outbox
     *
     * @param string $title
     * @param string $body
     * @param string $to
     * @param string[]|null $cc
     * @return int The HTTP Status code of the request.
     */
    public function postArticle(Blog $article, ?string $to, ?array $cc = null)
    {
        $this->validate();

        // Default the "To" to the user's subscribers, although it could be any other user,
        // even a user on another ActivityPub site.
        $to = $to ?? $this->actorURI . '/subscribers';

        $params = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                '@language' => 'en-US'
            ],
            'id'           => $this->config->site_url . "newsfeed/{$article->guid}",
            'type'         => 'Article',
            'name'         => $article->getTitle(),
            'content'      => $article->getBody(),
            'attributedTo' => $this->actorURI,
            'to'           => $to,
            'cc'           => $cc,
        ];

        $this->response = $this->client->post($this->activityPubURI, [
            'Content-Type' => 'application/json',
            'json'         => $params,
        ]);

        return $this->response->getStatusCode();
    }

    /**
     * See: https://w3c.github.io/activitypub/#create-activity-outbox
     */
    public function like(string $refObjectURI, ?string $to, ?string $summary = null, ?array $cc = null)
    {
        $this->validate();

        // Default the "To" to the user's subscribers, although it could be any other user,
        // even a user on another ActivityPub site.
        $to = $to ?? $this->actorURI . '/subscribers';

        $params = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                '@language' => 'en-US'
            ],
            // Use the item's GUID as the basis for the unique ActivityPub ID.
            'id'      => $this->config->site_url . $this->actorURI . '/activitypub/' . $refObjectURI,
            'type'    => 'Like',
            'actor'   => $this->actorURI,
            'summary' => $summary ?? "{$this->actorName} liked the post",
            'object'  => $refObjectURI,
            'to'      => $to,
            'cc'      => $cc,
        ];

        $this->response = $this->client->post($this->activityPubURI, [
            'Content-Type' => 'application/json',
            'json'         => $params,
        ]);

        return $this->response->getStatusCode();
    }
}
