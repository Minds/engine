<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\EventStreams;

use Minds\Core\MultiTenant\Bootstrap\EventStreams\TenantBootstrapRequestSubscription;
use Minds\Core\MultiTenant\Bootstrap\Services\MultiTenantBootstrapService;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\TenantBootstrapRequestEvent;
use Minds\Core\EventStreams\Topics\TenantBootstrapRequestsTopic;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class TenantBootstrapRequestSubscriptionSpec extends ObjectBehavior
{
    private Collaborator $multiTenantBootstrapServiceMock;
    private Collaborator $loggerMock;

    public function let(MultiTenantBootstrapService $multiTenantBootstrapServiceMock, Logger $loggerMock)
    {
        $this->multiTenantBootstrapServiceMock = $multiTenantBootstrapServiceMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($multiTenantBootstrapServiceMock, $loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TenantBootstrapRequestSubscription::class);
    }

    public function it_should_get_subscription_id()
    {
        $this->getSubscriptionId()->shouldReturn('tenant-bootstrap-requests-subscription');
    }

    public function it_should_get_topic()
    {
        $this->getTopic()->shouldBeAnInstanceOf(TenantBootstrapRequestsTopic::class);
    }

    public function it_should_get_topic_regex()
    {
        $this->getTopicRegex()->shouldReturn(TenantBootstrapRequestsTopic::TOPIC);
    }

    public function it_should_consume_event(TenantBootstrapRequestEvent $event)
    {
        $siteUrl = 'https://example.com';
        $tenantId = 1;

        $event->getSiteUrl()->willReturn($siteUrl);
        $event->getTenantId()->willReturn($tenantId);

        $this->multiTenantBootstrapServiceMock->bootstrap($siteUrl, $tenantId)->shouldBeCalled();

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_log_error_if_bootstrap_fails(TenantBootstrapRequestEvent $event)
    {
        $siteUrl = 'https://example.minds.com';
        $tenantId = 1;
        $exceptionMessage = 'Bootstrap failed';

        $event->getSiteUrl()->willReturn($siteUrl);
        $event->getTenantId()->willReturn($tenantId);

        $this->multiTenantBootstrapServiceMock->bootstrap($siteUrl, $tenantId)
            ->willThrow(new \Exception($exceptionMessage));

        $this->loggerMock->error("Error bootstrapping tenant {$tenantId}: {$exceptionMessage}", Argument::any())->shouldBeCalled();

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_acknowledge_non_tenant_bootstrap_request_event(EventInterface $event)
    {
        $this->multiTenantBootstrapServiceMock->bootstrap(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->consume($event)->shouldReturn(true);
    }
}
