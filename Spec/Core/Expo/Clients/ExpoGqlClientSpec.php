<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Expo\Clients;

use Minds\Core\Expo\Clients\ExpoGqlClient;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Log\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ExpoGqlClientSpec extends ObjectBehavior
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
        $this->logger = $logger;
        $this->expoConfig = $expoConfig;

        $this->beConstructedWith($guzzleClient, $expoConfig, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ExpoGqlClient::class);
    }

    public function it_can_make_a_request()
    {
        $body = ['query' => '{ test { id } }'];
        $responseBody = ['data' => ['test' => ['id' => '123']]];

        $this->expoConfig->gqlApiUrl = 'https://expo.io/graphql';
        $this->expoConfig->bearerToken = 'token';

        $this->guzzleClient->request('POST', 'https://expo.io/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token'
            ],
            'body' => json_encode($body)
        ])
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], json_encode($responseBody)));

        $this->request($body)->shouldReturn($responseBody);
    }

    public function it_throws_an_exception_when_the_api_returns_an_error()
    {
        $body = ['query' => '{ viewer { id } }'];
        $responseBody = ['errors' => [['message' => 'Something went wrong']]];

        $this->expoConfig->gqlApiUrl = 'https://expo.io/graphql';
        $this->expoConfig->bearerToken = 'token';

        $this->guzzleClient->request('POST', 'https://expo.io/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token'
            ],
            'body' => json_encode($body)
        ])
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], json_encode($responseBody)));

        $this->logger->error(Argument::type('string'))->shouldBeCalled();
        $this->shouldThrow(ServerErrorException::class)->duringRequest($body);
    }
}
