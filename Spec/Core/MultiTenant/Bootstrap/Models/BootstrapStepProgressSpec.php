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
        $stepName = BootstrapStepEnum::CONTENT_STEP;
        $success = true;
        $lastRunTimestamp = new \DateTime();

        $this->beConstructedWith($tenantId, $stepName, $success, $lastRunTimestamp);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BootstrapStepProgress::class);
    }

    public function it_should_return_tenant_id()
    {
        $this->getTenantId()->shouldReturn(123);
    }

    public function it_should_return_step_name()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::CONTENT_STEP, true, new \DateTime());
        $this->getStepName()->shouldReturn(BootstrapStepEnum::CONTENT_STEP);
    }

    public function it_should_return_success()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::CONTENT_STEP, true, new \DateTime());
        $this->getSuccess()->shouldReturn(true);
    }

    public function it_should_return_last_run_timestamp()
    {
        $this->getLastRunTimestamp()->shouldBeAnInstanceOf(\DateTime::class);
    }

    public function it_should_return_step_loading_label()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::CONTENT_STEP, true, new \DateTime());
        $this->getStepLoadingLabel()->shouldReturn('Getting your content ready...');
    }

    public function it_should_serialize_to_json()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::CONTENT_STEP, true, new \DateTime());
        $this->jsonSerialize()->shouldReturn([
            'tenantId' => 123,
            'stepName' => 'CONTENT_STEP',
            'stepLoadingLabel' => 'Getting your content ready...',
            'success' => true,
            'lastRunTimestamp' => $this->getLastRunTimestamp()->getWrappedObject()->getTimestamp(),
        ]);
    }

    public function it_should_return_tenant_config_step_loading_label()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::TENANT_CONFIG_STEP, true, new \DateTime());
        $this->getStepLoadingLabel()->shouldReturn('Configuring your network...');
    }

    public function it_should_return_logo_step_loading_label()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::LOGO_STEP, true, new \DateTime());
        $this->getStepLoadingLabel()->shouldReturn('Building your logos...');
    }

    public function it_should_return_finished_step_loading_label()
    {
        $this->beConstructedWith(123, BootstrapStepEnum::FINISHED, true, new \DateTime());
        $this->getStepLoadingLabel()->shouldReturn('Your network is ready!');
    }
}
