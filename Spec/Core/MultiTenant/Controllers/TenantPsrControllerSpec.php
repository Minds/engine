<?php

namespace Spec\Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Controllers\TenantPsrController;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\AutoTrialService;
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

    public function it_should_start_trial(ServerRequestInterface $requestMock)
    {
        $requestMock->getParsedBody()
            ->willReturn([
                'email' => 'hello@minds.com'
            ]);
        
        $this->trialServiceMock->startTrialWithEmail('hello@minds.com')
            ->willReturn(new Tenant(id: 1));

        $this->startTrial($requestMock);
    }
}
