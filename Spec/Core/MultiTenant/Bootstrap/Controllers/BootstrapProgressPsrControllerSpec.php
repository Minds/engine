<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Controllers;

use Minds\Core\MultiTenant\Bootstrap\Controllers\BootstrapProgressPsrController;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Models\BootstrapStepProgress;
use Minds\Core\MultiTenant\Bootstrap\Services\BootstrapProgressService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class BootstrapProgressPsrControllerSpec extends ObjectBehavior
{
    private Collaborator $bootstrapProgressServiceMock;

    public function let(BootstrapProgressService $bootstrapProgressServiceMock)
    {
        $this->bootstrapProgressServiceMock = $bootstrapProgressServiceMock;
        $this->beConstructedWith($bootstrapProgressServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BootstrapProgressPsrController::class);
    }

    public function it_should_get_bootstrap_progress(ServerRequestInterface $request)
    {
        $progressData = ['progress' => [
            new BootstrapStepProgress(
                tenantId: 1,
                stepName: BootstrapStepEnum::CONTENT_STEP,
                success: true,
                lastRunTimestamp: new \DateTime()
            )
        ]];
        $this->bootstrapProgressServiceMock->getProgress()->willReturn($progressData);

        $response = $this->getProgress($request);
        $response->shouldBeAnInstanceOf(JsonResponse::class);
        $response->getPayload()->shouldReturn($progressData);
    }
}
