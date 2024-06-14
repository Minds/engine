<?php

namespace Spec\Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Controllers\TenantPsrController;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\AutoTrialService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ServerRequestInterface;

class TenantPsrControllerSpec extends ObjectBehavior
{
    private Collaborator $trialServiceMock;

    public function let(AutoTrialService $trialServiceMock)
    {
        $this->beConstructedWith($trialServiceMock);
        $this->trialServiceMock = $trialServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TenantPsrController::class);
    }

    public function it_should_start_trial(ServerRequestInterface $requestMock, User $userMock)
    {
        $requestMock->getParsedBody()
            ->willReturn([
                'email' => 'hello@minds.com'
            ]);
        
        $requestMock->getAttribute('_user')
            ->willReturn($userMock);

        $userMock->get('admin')
            ->willReturn('yes');

        $this->trialServiceMock->startTrialWithEmail('hello@minds.com')
            ->willReturn(new Tenant(id: 1));

        $this->startTrial($requestMock);
    }

    public function it_should_not_start_trial_if_not_admin(ServerRequestInterface $requestMock, User $userMock)
    {
        $requestMock->getParsedBody()
            ->willReturn([
                'email' => 'hello@minds.com'
            ]);
        
        $requestMock->getAttribute('_user')
            ->willReturn($userMock);

        $userMock->get('admin')
            ->willReturn('no');

        $this->trialServiceMock->startTrialWithEmail('hello@minds.com')
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringStartTrial($requestMock);
    }
}
