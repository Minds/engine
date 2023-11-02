<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Expo\Clients;

use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Log\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Minds\Core\Expo\Clients\ExpoHttpClient;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ExpoHttpClientSpec extends ObjectBehavior
{
    private Collaborator $guzzleClient;
    private Collaborator $logger;
    private Collaborator $expoConfig;

    public function let(
        GuzzleClient $guzzleClient,
        Logger $logger,
        ExpoConfig $expoConfig
    ) {
        $this->guzzleClient = $guzzleClient;
        $this->expoConfig = $expoConfig;
        $this->logger = $logger;

        $this->beConstructedWith($guzzleClient, $expoConfig, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ExpoHttpClient::class);
    }

    public function it_can_make_a_request()
    {
        $method = 'POST';
        $path = $this->V2_PROJECTS_PATH;
        $body = ['query' => '{ test { id } }'];
        $responseBody = ['data' => ['test' => ['id' => '123']]];

        $this->expoConfig->httpApiBaseUrl = 'https://expo.io/';
        $this->expoConfig->bearerToken = 'token';

        $this->guzzleClient->request($method, $this->expoConfig->httpApiBaseUrl . $path, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token'
            ],
            'body' => json_encode($body)
        ])
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], json_encode($responseBody)));

        $this->request($method, $path, $body)->shouldReturn($responseBody['data']);
    }

    public function it_throws_an_exception_when_the_api_returns_an_error()
    {
        $method = 'POST';
        $path = $this->V2_PROJECTS_PATH;
        $body = ['query' => '{ test { id } }'];
        $responseBody = ['errors' => [['message' => 'Something went wrong']]];

        $this->expoConfig->httpApiBaseUrl = 'https://expo.io/';
        $this->expoConfig->bearerToken = 'token';

        $this->guzzleClient->request($method, $this->expoConfig->httpApiBaseUrl . $path, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token'
            ],
            'body' => json_encode($body)
        ])
            ->shouldBeCalled()
            ->willThrow(new ClientException(
                'Something went wrong',
                new Request('GET', 'url'),
                new Response(
                    500,
                    [],
                    json_encode($responseBody)
                )
            ));

        $this->logger->error(Argument::any())->shouldBeCalled();
        $this->shouldThrow(ServerErrorException::class)->duringRequest($method, $path, $body);
    }
}
