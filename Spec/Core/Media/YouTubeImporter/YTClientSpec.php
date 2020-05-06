<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Core\Media\YouTubeImporter\YTClient;
use Minds\Core\Config;
use Google_Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class YTClientSpec extends ObjectBehavior
{
    /** @var Google_Client */
    protected $client;

    /** @var Config */
    protected $config;

    public function let(
        Google_Client $client,
        Config $config
    ) {
        $this->client = $client;
        $this->config = $config;
    
        $this->beConstructedWith($client, $config);

        $config->get('site_url')
            ->willReturn('https://phpspec.minds.io/');

        $client->addScope(\Google_Service_YouTube::YOUTUBE_READONLY)
            ->shouldBeCalled();
        $client->setRedirectUri('https://phpspec.minds.io/api/v3/media/youtube-importer/account/redirect')
            ->shouldBeCalled();
        $client->setAccessType('offline')
            ->shouldBeCalled();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(YTClient::class);
    }

    public function it_should_return_a_client()
    {
        $this->config->get('google')
            ->shouldBeCalled()
            ->willReturn([
                'youtube' => [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                ],
            ]);
    
        $this->client->setDeveloperKey('')
            ->shouldBeCalled();

        $this->client->setClientId('client_id')
            ->shouldBeCalled();

        $this->client->setClientSecret('client_secret')
            ->shouldBeCalled();

        $this->getClient(false);
    }

    public function it_should_return_a_server_client()
    {
        $this->config->get('google')
            ->shouldBeCalled()
            ->willReturn([
                'youtube' => [
                    'api_key' => 'api_key',
                ],
            ]);
    
        $this->client->setDeveloperKey('api_key')
            ->shouldBeCalled();

        $this->client->setClientId('')
            ->shouldBeCalled();

        $this->client->setClientSecret('')
            ->shouldBeCalled();

        $this->getClient(true);
    }
}
