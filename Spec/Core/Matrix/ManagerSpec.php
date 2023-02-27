<?php
namespace Spec\Minds\Core\Matrix;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Matrix\Client;
use Minds\Core\Matrix\Manager;
use Minds\Core\Matrix\MatrixConfig;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

class ManagerSpec extends ObjectBehavior
{
    protected Collaborator $client;
    protected Collaborator $matrixConfig;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $logger;

    public function let(
        Client $client = null,
        MatrixConfig $matrixConfig,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    )
    {
        $this->client = $client;
        $this->matrixConfig = $matrixConfig;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;

        $this->beConstructedWith(
            $client,
            $matrixConfig,
            $entitiesBuilder,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_throw_403_when_matrix_returns_401_on_failure_getting_direct_rooms(
        User $sender,
        User $receiver,
        ResponseInterface $getTokenResponse
    ) {
        $homeserverDomainName = 'homeserver.minds.com';
        
        $senderUsername = 'testSender';
        $receiverUsername = 'testReceiver';
        
        $accessToken = 'abc123abc';

        $sender->getUsername()
            ->shouldBeCalled()
            ->willReturn($senderUsername);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $this->matrixConfig->getHomeserverDomain()
            ->shouldBeCalled()
            ->willReturn($homeserverDomainName);

        // get access token

        $getTokenResponse->getBody()
            ->shouldBeCalled()
            ->willReturn(json_encode(['access_token' => $accessToken ]));

        $this->client->request('POST', Argument::type('string'), Argument::any())
            ->shouldBeCalled()
            ->willReturn($getTokenResponse);

        $this->client->setAccessToken($accessToken)
            ->shouldBeCalled()
            ->willReturn($this->client);

        // req to get direct rooms

        $this->client->request('GET', Argument::type('string'))
            ->shouldBeCalled()
            ->willThrow(new ClientException(
                'Invalid macaroon passed',
                new Request('GET', 'url'), 
                new Response(
                    401,
                    [],
                    '{"errcode":"M_UNKNOWN_TOKEN","error":"Invalid macaroon passed.","soft_logout":false}'
                )
            ));

        $this->shouldThrow(new ForbiddenException('Please login to Minds Chat'))->during('createDirectRoom', [$sender, $receiver]);
    }

    public function it_should_rethrow_handled_500_on_failure_getting_direct_rooms(
        User $sender,
        User $receiver,
        ResponseInterface $getTokenResponse
    ) {
        $homeserverDomainName = 'homeserver.minds.com';
        
        $senderUsername = 'testSender';
        $receiverUsername = 'testReceiver';
        
        $accessToken = 'abc123abc';

        $sender->getUsername()
            ->shouldBeCalled()
            ->willReturn($senderUsername);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $this->matrixConfig->getHomeserverDomain()
            ->shouldBeCalled()
            ->willReturn($homeserverDomainName);

        // get access token

        $getTokenResponse->getBody()
            ->shouldBeCalled()
            ->willReturn(json_encode(['access_token' => $accessToken ]));

        $this->client->request('POST', Argument::type('string'), Argument::any())
            ->shouldBeCalled()
            ->willReturn($getTokenResponse);

        $this->client->setAccessToken($accessToken)
            ->shouldBeCalled()
            ->willReturn($this->client);

        // req to get direct rooms

        $this->client->request('GET', Argument::type('string'))
            ->shouldBeCalled()
            ->willThrow(new ClientException(
                'Invalid macaroon passed',
                new Request('GET', 'url'), 
                new Response(
                    500,
                    [],
                    '{"errcode":"M_UNKNOWN_TOKEN","error":"Invalid macaroon passed.","soft_logout":false}'
                )
            ));

        $this->shouldThrow(new ServerErrorException('Unable to get rooms for this user'))->during('createDirectRoom', [$sender, $receiver]);
    }
}
