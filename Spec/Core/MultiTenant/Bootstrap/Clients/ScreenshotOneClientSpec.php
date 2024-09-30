<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Clients;

use Minds\Core\MultiTenant\Bootstrap\Clients\ScreenshotOneClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ScreenshotOneClientSpec extends ObjectBehavior
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
        $this->shouldHaveType(ScreenshotOneClient::class);
    }

    public function it_should_fetch_screenshot()
    {
        $siteUrl = 'https://example.minds.com';
        $viewportWidth = 1920;
        $viewportHeight = 1080;
        $baseUrl = 'https://api.screenshotone.com/';
        $apiKey = 'test-api-key';
        $queryString = http_build_query([
            'access_key' => $apiKey,
            'url' => $siteUrl,
            'full_page' => 'true',
            'full_page_scroll' => 'false',
            'viewport_width' => $viewportWidth,
            'viewport_height' => $viewportHeight,
            'device_scale_factor' => 1,
            'format' => 'jpg',
            'image_quality' => 80,
            'block_ads' => 'true',
            'block_cookie_banners' => 'true',
            'block_banners_by_heuristics' => 'false',
            'block_trackers' => 'true',
            'delay' => 0,
            'timeout' => ScreenshotOneClient::TIMEOUT
        ]);

        $url = $baseUrl . 'take?' . $queryString;
        $responseBody = 'screenshot-blob';

        $response = new Response(200, [], $responseBody);

        $this->configMock->get('screenshot_one')->willReturn(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $this->guzzleClientMock->request('GET', $url, ['timeout' => ScreenshotOneClient::TIMEOUT])
            ->willReturn($response);

        $this->get($siteUrl, $viewportWidth, $viewportHeight)->shouldReturn($responseBody);
    }

    public function it_should_return_empty_blob_if_no_response()
    {
        $siteUrl = 'https://example.minds.com';
        $viewportWidth = 1920;
        $viewportHeight = 1080;
        $baseUrl = 'https://api.screenshotone.com/';
        $apiKey = 'test-api-key';
        $queryString = http_build_query([
            'access_key' => $apiKey,
            'url' => $siteUrl,
            'full_page' => 'true',
            'full_page_scroll' => 'false',
            'viewport_width' => $viewportWidth,
            'viewport_height' => $viewportHeight,
            'device_scale_factor' => 1,
            'format' => 'jpg',
            'image_quality' => 80,
            'block_ads' => 'true',
            'block_cookie_banners' => 'true',
            'block_banners_by_heuristics' => 'false',
            'block_trackers' => 'true',
            'delay' => 0,
            'timeout' => ScreenshotOneClient::TIMEOUT
        ]);

        $url = $baseUrl . 'take?' . $queryString;

        $response = new Response(200, [], null);

        $this->configMock->get('screenshot_one')->willReturn(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $this->guzzleClientMock->request('GET', $url, ['timeout' => ScreenshotOneClient::TIMEOUT])
            ->willReturn($response);

        $this->get($siteUrl, $viewportWidth, $viewportHeight)->shouldReturn("");
    }
}
