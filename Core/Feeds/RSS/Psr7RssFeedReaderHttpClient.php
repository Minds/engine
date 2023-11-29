<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Feed\Reader\Http\ClientInterface;
use Laminas\Feed\Reader\Http\Psr7ResponseDecorator;

class Psr7RssFeedReaderHttpClient implements ClientInterface
{
    public function __construct(
        private readonly Client $client
    ) {
    }

    /**
     * @inheritDoc
     * @throws GuzzleException
     */
    public function get($uri): Psr7ResponseDecorator
    {
        return new Psr7ResponseDecorator(
            $this->client->request('GET', $uri)
        );
    }
}
