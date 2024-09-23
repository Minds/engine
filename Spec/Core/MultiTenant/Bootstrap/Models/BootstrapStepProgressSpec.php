<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Models;

use Minds\Core\MultiTenant\Bootstrap\Models\BootstrapStepProgress;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use PhpSpec\ObjectBehavior;

class BootstrapStepProgressSpec extends ObjectBehavior
{
    public function let()
    {
        $tenantId = 123;
        $step = BootstrapStepEnum::CONTENT_STEP;
        $success = true;
        $lastRunTimestamp = new \DateTime();

        $this->beConstructedWith($tenantId, $step, $success, $lastRunTimestamp);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BootstrapStepProgress::class);
    }

    public function it_should_return_tenant_id()
    {
        $this->getTenantId()->shouldReturn(123);
    }

    public function it_should_return_step()
    {
        $this->getStep()->shouldReturn(BootstrapStepEnum::CONTENT_STEP);
    }

    public function it_should_return_success()
    {
        $this->getSuccess()->shouldReturn(true);
    }

    public function it_should_return_last_run_timestamp()
    {
        $this->getLastRunTimestamp()->shouldBeAnInstanceOf(\DateTime::class);
    }

    public function it_should_serialize_to_json()
    {
        $this->jsonSerialize()->shouldReturn([
            'tenantId' => 123,
            'step' => 'CONTENT_STEP',
            'success' => true,
            'lastRunTimestamp' => $this->getLastRunTimestamp()->getWrappedObject()->getTimestamp(),
        ]);
    }
}
