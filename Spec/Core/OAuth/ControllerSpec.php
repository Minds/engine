<?php

namespace Spec\Minds\Core\OAuth;

use Minds\Core\OAuth\Controller;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\OAuth\Entities\ClientEntity;
use Minds\Core\OAuth\Entities\UserEntity;
use Minds\Core\OAuth\Repositories\AccessTokenRepository;
use Minds\Core\OAuth\Repositories\RefreshTokenRepository;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;

class ControllerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var AuthorizationServer */
    protected $authorizationServer;

    /** @var AccessTokenRepository */
    protected $accessTokenRepository;

    /** @var RefreshTokenRepository */
    protected $refreshTokenRepository;

    public function let(
        Config $config,
        AuthorizationServer $authorizationServer,
        AccessTokenRepository $accessTokenRepository,
        RefreshTokenRepository $refreshTokenRepository
    ) {
        $this->beConstructedWith($config, $authorizationServer, $accessTokenRepository, $refreshTokenRepository);
        $this->config = $config;
        $this->authorizationServer = $authorizationServer;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_complete_authorize_request(
        ServerRequest $request,
        User $user,
        AuthorizationRequest $authorizationRequest,
        ClientEntity $clientEntity
    ) {
        $request->getAttribute('_user')
            ->willReturn($user);

        $this->authorizationServer->validateAuthorizationRequest($request)
            ->willReturn($authorizationRequest);

        $authorizationRequest->setUser(Argument::any())
            ->shouldBeCalled();

        $authorizationRequest->getClient()
            ->willReturn($clientEntity);

        $authorizationRequest->setRedirectUri('redirect-uri-set-by-client')
            ->shouldBeCalled();
        ;

        $authorizationRequest->setAuthorizationApproved(true)
            ->shouldBeCalled();

        $clientEntity
            ->getIdentifier()->willReturn('matrix');

        $clientEntity->getRedirectUri()
            ->willReturn('redirect-uri-set-by-client');

        $this->authorizationServer->completeAuthorizationRequest($authorizationRequest, Argument::any())
            ->willReturn(new JsonResponse([]));

        $this->authorize($request);
    }

    public function it_should_get_token_from_request(ServerRequest $request)
    {
        $this->authorizationServer->respondToAccessTokenRequest($request, Argument::any())
            ->willReturn(new JsonResponse([]));
        
        $response = $this->token($request);

        $response
            ->getBody()
            ->getContents()
            ->shouldBe(json_encode(['status' => 'success']));
    }
}
