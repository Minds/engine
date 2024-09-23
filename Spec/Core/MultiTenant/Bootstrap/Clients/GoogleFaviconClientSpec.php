<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Clients;

use Minds\Core\MultiTenant\Bootstrap\Clients\GoogleFaviconClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class GoogleFaviconClientSpec extends ObjectBehavior
{
    private Collaborator $guzzleClientMock;
    private Collaborator $configMock;

    public function let(GuzzleClient $guzzleClientMock, Config $configMock)
    {
        $this->guzzleClientMock = $guzzleClientMock;
        $this->configMock = $configMock;

        $this->beConstructedWith($guzzleClientMock, $configMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GoogleFaviconClient::class);
    }

    public function it_should_fetch_favicon()
    {
        $url = 'https://example.minds.com/';
        $size = 32;
        $domain = 'example.minds.com';
        $faviconUrl = "https://www.google.com/s2/favicons?domain=$domain&sz=$size";
        $responseBody = 'favicon-blob';

        $response = new Response(200, [], $responseBody);
        $this->guzzleClientMock->get($faviconUrl, ['timeout' => GoogleFaviconClient::TIMEOUT])
            ->willReturn($response);

        $this->get($url, $size)->shouldReturn($responseBody);
    }

    public function it_should_return_empty_blob_if_no_response()
    {
        $url = 'https://example.com';
        $size = 32;
        $domain = 'example.com';
        $faviconUrl = "https://www.google.com/s2/favicons?domain=$domain&sz=$size";

        $this->guzzleClientMock->get($faviconUrl, ['timeout' => GoogleFaviconClient::TIMEOUT])
            ->willReturn(new Response(200, [], null));

        $this->get($url, $size)->shouldReturn("");
    }
}
