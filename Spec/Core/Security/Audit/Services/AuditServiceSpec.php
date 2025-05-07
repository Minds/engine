<?php

namespace Spec\Minds\Core\Security\Audit\Services;

use Minds\Common\IpAddress;
use Minds\Core\Analytics\PostHog\PostHogQueryService;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Audit\Repositories\AuditRepository;
use Minds\Core\Security\Audit\Services\AuditService;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class AuditServiceSpec extends ObjectBehavior
{
    private Collaborator $auditRepositoryMock;
    private Collaborator $activeSessionMock;
    private Collaborator $loggerMock;
    private Collaborator $ipAddressMock;
    private Collaborator $postHogQueryServiceMock;
    private Collaborator $configMock;

    public function let(
        AuditRepository $auditRepositoryMock,
        ActiveSession $activeSessionMock,
        Logger $loggerMock,
        IpAddress $ipAddressMock,
        PostHogQueryService $postHogQueryServiceMock,
        Config $configMock
    ) {
        $this->beConstructedWith(
            $auditRepositoryMock,
            $activeSessionMock,
            $loggerMock,
            $ipAddressMock,
            $postHogQueryServiceMock,
            $configMock,
        );

        $this->auditRepositoryMock = $auditRepositoryMock;
        $this->activeSessionMock = $activeSessionMock;
        $this->loggerMock = $loggerMock;
        $this->ipAddressMock = $ipAddressMock;
        $this->postHogQueryServiceMock = $postHogQueryServiceMock;
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AuditService::class);
    }

    public function it_logs_an_audit_event(
        User $user
    ) {
        $user->getGuid()->willReturn('123');
        $this->activeSessionMock->getUser()->willReturn($user);
    
        $this->ipAddressMock->get()->willReturn('127.0.0.1');

        $this->auditRepositoryMock->log(
            'login_attempt',
            123,
            ['success' => true],
            '127.0.0.1',
            '',
            ''
        )->shouldBeCalled();

        $this->loggerMock->info('[Audit]: login_attempt', [
            'user_guid' => '123',
            'success' => true
        ])->shouldBeCalled();

        $this->log('login_attempt', ['success' => true]);
    }

    public function it_returns_audit_logs(
    ) {
        $rows = [
            ['event_id' => 101, 'event' => 'login', 'user_guid' => 'user-1'],
            ['event_id' => 102, 'event' => 'logout', 'user_guid' => 'user-1'],
        ];

        $this->auditRepositoryMock->list(10, null, null, null, null)->willReturn($rows);

        $this->getLogs(10, null, null)->shouldReturn($rows);
    }

    public function it_returns_analytics_events_from_posthog(
    ) {
        $this->configMock->get('tenant_id')->willReturn(42);

        $results = [
            ['uuid1', 'pageview', 'user-1', '{"path":"/home"}', '2024-05-01T12:00:00Z'],
            ['uuid2', 'click', 'user-1', '{"button":"login"}', '2024-05-01T12:01:00Z'],
        ];

        $this->postHogQueryServiceMock->query(Argument::any())->willReturn([
            'results' => $results
        ]);


        $expected = [
            [
                'event_id' => 'uuid1',
                'event' => 'pageview',
                'user_guid' => 'user-1',
                'properties' => (object)['path' => '/home'],
                'created_at' => '2024-05-01T12:00:00Z',
            ],
            [
                'event_id' => 'uuid2',
                'event' => 'click',
                'user_guid' => 'user-1',
                'properties' => (object)['button' => 'login'],
                'created_at' => '2024-05-01T12:01:00Z',
            ],
        ];

        $this->getEvents(10)->shouldBeLike($expected);
    }
}
