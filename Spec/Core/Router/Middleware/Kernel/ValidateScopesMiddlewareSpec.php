<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Middleware\Kernel\ValidateScopesMiddleware;
use Minds\Core\Router\RegistryEntry;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidateScopesMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ValidateScopesMiddleware::class);
    }

    public function it_should_allow_access_to_logged_out_users(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        RegistryEntry $requestHandlerMock,
        ResponseInterface $responseMock,
    ) {
        $requestMock->getAttribute(RequestAttributeEnum::REQUEST_HANDLER)
            ->willReturn($requestHandlerMock);
        $requestMock->getAttribute(RequestAttributeEnum::USER)
            ->willReturn(null);

        $handlerMock->handle($requestMock)
            ->shouldBeCalled()
            ->willReturn($responseMock);
    
        $this->process($requestMock, $handlerMock)
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    public function it_should_allow_access_if_we_have_the_all_scope(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        RegistryEntry $requestHandlerMock,
        ResponseInterface $responseMock,
    ) {

        $requestMock->getAttribute(RequestAttributeEnum::REQUEST_HANDLER)
            ->willReturn($requestHandlerMock);
        $requestMock->getAttribute(RequestAttributeEnum::USER)
            ->willReturn(new User());

        $requestHandlerMock->getScopes()->willReturn([]); // No additional scopes for route
    
        $requestMock->getAttribute(RequestAttributeEnum::SCOPES)
            ->willReturn([ApiScopeEnum::ALL]);

        $handlerMock->handle($requestMock)
            ->shouldBeCalled()
            ->willReturn($responseMock);
    
        $this->process($requestMock, $handlerMock)
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    public function it_should_allow_access_if_we_have_a_specific_scope(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        RegistryEntry $requestHandlerMock,
        ResponseInterface $responseMock,
    ) {

        $requestMock->getAttribute(RequestAttributeEnum::REQUEST_HANDLER)
            ->willReturn($requestHandlerMock);
        $requestMock->getAttribute(RequestAttributeEnum::USER)
            ->willReturn(new User());

        $requestHandlerMock->getScopes()->willReturn([
            ApiScopeEnum::SITE_MEMBERSHIP_WRITE
        ]);
    
        $requestMock->getAttribute(RequestAttributeEnum::SCOPES)
            ->willReturn([ApiScopeEnum::SITE_MEMBERSHIP_WRITE]);

        $handlerMock->handle($requestMock)
            ->shouldBeCalled()
            ->willReturn($responseMock);
    
        $this->process($requestMock, $handlerMock)
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    public function it_should_allow_access_if_we_dont_have_the_correct_scope(
        ServerRequestInterface $requestMock,
        RequestHandlerInterface $handlerMock,
        RegistryEntry $requestHandlerMock,
    ) {

        $requestMock->getAttribute(RequestAttributeEnum::REQUEST_HANDLER)
            ->willReturn($requestHandlerMock);
        $requestMock->getAttribute(RequestAttributeEnum::USER)
            ->willReturn(new User());

        $requestHandlerMock->getScopes()->willReturn([]); // No additional scopes for route
    
        $requestMock->getAttribute(RequestAttributeEnum::SCOPES)
            ->willReturn([ApiScopeEnum::SITE_MEMBERSHIP_WRITE]);

        $handlerMock->handle($requestMock)
            ->shouldNotBeCalled();
    
        $this->shouldThrow(ForbiddenException::class)->duringProcess($requestMock, $handlerMock);
    }
}
