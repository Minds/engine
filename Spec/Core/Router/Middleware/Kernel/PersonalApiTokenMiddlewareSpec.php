<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyAuthService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Router\Middleware\Kernel\PersonalApiTokenMiddleware;
use Minds\Core\Router\RegistryEntry;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PersonalApiTokenMiddlewareSpec extends ObjectBehavior
{
    private Collaborator $personalApiKeyAuthServiceMock;
    private Collaborator $entitiesBuilderMock;

    public function let(
        PersonalApiKeyAuthService $personalApiKeyAuthServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
    ) {
        $this->beConstructedWith($personalApiKeyAuthServiceMock, $entitiesBuilderMock);
        $this->personalApiKeyAuthServiceMock = $personalApiKeyAuthServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PersonalApiTokenMiddleware::class);
    }

    public function it_should_process_with_a_valid_api_key(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        ResponseInterface $responseMock,
    ) {
        $requestMock->getHeader('Authorization')
            ->willReturn([
                'Bearer pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063'
            ]);

        $userGuid = Guid::build();
        $user = new User();

        $personalApiKey = $this->buildMockPersonalApiKey(
            $userGuid,
        );

        $this->personalApiKeyAuthServiceMock->getKeyBySecret('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063')
            ->willReturn($personalApiKey);

        $this->entitiesBuilderMock->single($userGuid)
            ->shouldBeCalledOnce()
            ->willReturn($user);

        $this->personalApiKeyAuthServiceMock->validateKey($personalApiKey)
            ->willReturn(true);

        $requestMock->withAttribute('_user', $user)
            ->shouldBeCalledOnce()
            ->willReturn($requestMock);
        $requestMock->withAttribute('personal_api_key', $personalApiKey)
            ->shouldBeCalledOnce()
            ->willReturn($requestMock);
        $requestMock->withAttribute('scopes', [])
            ->shouldBeCalledOnce()
            ->willReturn($requestMock);

        $handlerMock->handle($requestMock)
            ->shouldBeCalled()
            ->willReturn($responseMock);
    
        $this->process($requestMock, $handlerMock)
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    public function it_should_throw_if_invalid_api_key(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
    ) {
        $requestMock->getHeader('Authorization')
            ->willReturn([
                'Bearer pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063'
            ]);

        $userGuid = Guid::build();
        $user = new User();

        $personalApiKey = $this->buildMockPersonalApiKey(
            $userGuid,
        );

        $this->personalApiKeyAuthServiceMock->getKeyBySecret('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063')
            ->willReturn($personalApiKey);

        $this->entitiesBuilderMock->single($userGuid)
            ->shouldBeCalledOnce()
            ->willReturn($user);

        $this->personalApiKeyAuthServiceMock->validateKey($personalApiKey)
            ->willReturn(false);

        $requestMock->withAttribute('_user', $user)
            ->shouldNotBeCalled();
        $requestMock->withAttribute('personal_api_key', $personalApiKey)
            ->shouldNotBeCalled();
        $requestMock->withAttribute('scopes', [])
            ->shouldNotBeCalled();

        $handlerMock->handle($requestMock)
            ->shouldNotBeCalled();
    
        $this->shouldThrow(UnauthorizedException::class)->duringProcess($requestMock, $handlerMock);
    }

    public function it_should_throw_if_user_is_banned(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
    ) {
        $requestMock->getHeader('Authorization')
            ->willReturn([
                'Bearer pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063'
            ]);

        $userGuid = Guid::build();
        $user = new User();
        $user->banned = 'yes';

        $personalApiKey = $this->buildMockPersonalApiKey(
            $userGuid,
        );

        $this->personalApiKeyAuthServiceMock->getKeyBySecret('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063')
            ->willReturn($personalApiKey);

        $this->entitiesBuilderMock->single($userGuid)
            ->shouldBeCalledOnce()
            ->willReturn($user);

        $this->personalApiKeyAuthServiceMock->validateKey($personalApiKey)
            ->shouldNotBeCalled();

        $requestMock->withAttribute('_user', $user)
            ->shouldNotBeCalled();
        $requestMock->withAttribute('personal_api_key', $personalApiKey)
            ->shouldNotBeCalled();
        $requestMock->withAttribute('scopes', [])
            ->shouldNotBeCalled();

        $handlerMock->handle($requestMock)
            ->shouldNotBeCalled();
    
        $this->shouldThrow(UnauthorizedException::class)->duringProcess($requestMock, $handlerMock);
    }

    public function it_should_skip_if_no_auth_header(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        ResponseInterface $responseMock,
    ) {
        $requestMock->getHeader('Authorization')
            ->willReturn(null);

        $handlerMock->handle($requestMock)
            ->shouldBeCalled()
            ->willReturn($responseMock);
    
        $this->process($requestMock, $handlerMock)
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    
    public function it_should_skip_if_auth_header_but_not_pak(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        ResponseInterface $responseMock,
    ) {
        $requestMock->getHeader('Authorization')
            ->willReturn([
                'Bearer jwthere'
            ]);

        $handlerMock->handle($requestMock)
            ->shouldBeCalled()
            ->willReturn($responseMock);
    
        $this->process($requestMock, $handlerMock)
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    private function buildMockPersonalApiKey(int $userGuid): PersonalApiKey
    {
        return new PersonalApiKey(
            id: 'id',
            ownerGuid: $userGuid,
            secretHash: 'hash',
            name: 'name',
            scopes: [],
            timeCreated: new DateTimeImmutable(),
        );
    }
}
