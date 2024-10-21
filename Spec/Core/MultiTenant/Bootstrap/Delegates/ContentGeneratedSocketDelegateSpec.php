<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Config\Config;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Delegates\ContentGeneratedSocketDelegate;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ContentGeneratedSocketDelegateSpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $socketEventsMock;
    private Collaborator $loggerMock;

    public function let(Config $configMock, SocketEvents $socketEventsMock, Logger $loggerMock)
    {
        $this->configMock = $configMock;
        $this->socketEventsMock = $socketEventsMock;
        $this->loggerMock = $loggerMock;
        $this->beConstructedWith($socketEventsMock, $configMock, $loggerMock);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ContentGeneratedSocketDelegate::class);
    }

    public function it_should_emit_content_generated_event(): void
    {
        $this->configMock->get('tenant_id')->willReturn(123);

        $this->socketEventsMock->setRoom('tenant:bootstrap:123')
            ->shouldBeCalled()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit('tenant:bootstrap:123', json_encode([
            "step" => BootstrapStepEnum::CONTENT_STEP->name,
            "completed" => true
        ]))
            ->shouldBeCalled();

        $this->onContentGenerated();
    }

    public function it_should_emit_content_generated_event_with_custom_tenant_id(): void
    {
        $this->configMock->get('tenant_id')->willReturn(null);

        $this->socketEventsMock->setRoom('tenant:bootstrap:456')
            ->shouldBeCalled()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit('tenant:bootstrap:456', json_encode([
            "step" => BootstrapStepEnum::CONTENT_STEP->name,
            "completed" => true
        ]))
            ->shouldBeCalled();

        $this->onContentGenerated(456);
    }
}
