<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Router\Middleware;

use Minds\Core\Router\Middleware\PermissionsMiddleware;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Exceptions\RbacNotAllowed;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PermissionsMiddlewareSpec extends ObjectBehavior
{
    /** @var FederationEnabledService */
    private Collaborator $rolesServiceMock;

    public function let(
        RolesService $rolesServiceMock
    ) {
        $this->rolesServiceMock = $rolesServiceMock;
        $this->beConstructedWith(
            PermissionsEnum::CAN_MODERATE_CONTENT,
            $rolesServiceMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PermissionsMiddleware::class);
    }

    public function it_should_process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        User $user
    ) {
        $permission = PermissionsEnum::CAN_MODERATE_CONTENT;

        $this->beConstructedWith(
            $permission,
            $this->rolesServiceMock
        );

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->rolesServiceMock->hasPermission(
            $user,
            $permission
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_throw_rbac_not_allowed_exception_when_user_does_not_have_permission(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        User $user
    ) {
        $permission = PermissionsEnum::CAN_MODERATE_CONTENT;

        $this->beConstructedWith(
            $permission,
            $this->rolesServiceMock
        );

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->rolesServiceMock->hasPermission(
            $user,
            $permission
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this->shouldThrow(new RbacNotAllowed($permission))
            ->duringProcess($request, $handler);
    }
}
