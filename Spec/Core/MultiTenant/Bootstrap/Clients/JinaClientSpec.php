<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Clients;

use Minds\Core\MultiTenant\Bootstrap\Clients\JinaClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Bootstrap\Enums\JinaReturnFormat;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class JinaClientSpec extends ObjectBehavior
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
        $this->shouldHaveType(JinaClient::class);
    }

    public function it_should_fetch_data_from_jina_with_return_format()
    {
        $siteUrl = 'https://example.com';
        $baseUrl = 'https://r.jina.ai';
        $apiKey = 'test-api-key';
        $returnFormat = JinaReturnFormat::SCREENSHOT;
        $url = $baseUrl . $siteUrl;

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'X-Return-Format' => $returnFormat->value
        ];

        $responseBody = json_encode(['data' => ['content' => 'hello world']]);
        $response = new Response(200, [], $responseBody);

        $this->configMock->get('jina')->willReturn(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $this->guzzleClientMock->get($url, ['headers' => $headers, 'timeout' => JinaClient::TIMEOUT])
            ->willReturn($response);

        $this->get($siteUrl, $returnFormat)->shouldReturn(['content' => 'hello world']);
    }

    public function it_should_fetch_data_from_jina_with_no_return_format()
    {
        $siteUrl = 'https://example.com';
        $baseUrl = 'https://r.jina.ai';
        $apiKey = 'test-api-key';
        $returnFormat = null;
        $url = $baseUrl . $siteUrl;

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey
        ];

        $responseBody = json_encode(['data' => ['content' => 'hello world']]);
        $response = new Response(200, [], $responseBody);

        $this->configMock->get('jina')->willReturn(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $this->guzzleClientMock->get($url, ['headers' => $headers, 'timeout' => JinaClient::TIMEOUT])
            ->willReturn($response);

        $this->get($siteUrl, $returnFormat)->shouldReturn(['content' => 'hello world']);
    }

    public function it_should_return_null_if_no_data()
    {
        $siteUrl = 'https://example.com';
        $baseUrl = 'https://r.jina.ai';
        $apiKey = 'test-api-key';
        $url = $baseUrl . $siteUrl;

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey
        ];

        $responseBody = json_encode(['data' => null]);
        $response = new Response(200, [], $responseBody);

        $this->configMock->get('jina')->willReturn(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $this->guzzleClientMock->get($url, ['headers' => $headers, 'timeout' => JinaClient::TIMEOUT])
            ->willReturn($response);

        $this->get($siteUrl)->shouldReturn(null);
    }
}
