<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Queue\Runners;

use Minds\Core\Config\Config;
use Minds\Core\Email\Invites\Services\InviteProcessorService;
use Minds\Core\Email\Services\EmailAutoSubscribeService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\FeaturedEntityAutoSubscribeService;
use Minds\Core\Queue\LegacyClient;
use Minds\Core\Queue\Message;
use Minds\Core\Queue\Runners\Registered;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class RegisteredSpec extends ObjectBehavior
{
    private Collaborator $emailAutoSubscribeServiceMock;
    private Collaborator $featuredEntityAutoSubscribeServiceMock;
    private Collaborator $inviteProcessorServiceMock;

    private Collaborator $clientMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $configMock;

    public function let(
        EmailAutoSubscribeService          $emailAutoSubscribeService,
        FeaturedEntityAutoSubscribeService $featuredEntityAutoSubscribeService,
        InviteProcessorService             $inviteProcessorService,
        LegacyClient                       $client,
        EntitiesBuilder                    $entitiesBuilder,
        Config                             $config
    ): void {
        $this->emailAutoSubscribeServiceMock = $emailAutoSubscribeService;
        $this->featuredEntityAutoSubscribeServiceMock = $featuredEntityAutoSubscribeService;
        $this->inviteProcessorServiceMock = $inviteProcessorService;
        $this->clientMock = $client;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->configMock = $config;

        $this->beConstructedWith(
            $this->emailAutoSubscribeServiceMock,
            $this->featuredEntityAutoSubscribeServiceMock,
            $this->inviteProcessorServiceMock,
            $this->entitiesBuilderMock,
            $this->clientMock,
            $this->configMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Registered::class);
    }

    /**
     * @param Message $message
     * @param User $subscriber
     * @return void
     */
    public function it_should_process_post_registration_event_successfully_when_NO_tenant_NO_invite(
        Message $message,
        User    $subscriber
    ): void {
        $message->getData()->willReturn([
            'user_guid' => "1",
            'tenant_id' => null,
            'invite_token' => null
        ]);

        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $subscriber->subscribe('100000000000000519')
            ->shouldBeCalledOnce();

        $this->entitiesBuilderMock->single("1")
            ->shouldBeCalledOnce()
            ->willReturn($subscriber);

        $this->emailAutoSubscribeServiceMock->subscribeToDefaultEmails(1)
            ->shouldBeCalledOnce();

        $this->processPostRegistrationEvent($message);
    }

    /**
     * @param Message $message
     * @param User $subscriber
     * @return void
     */
    public function it_should_process_post_registration_event_successfully_when_WITH_tenant_NO_invite(
        Message $message,
        User    $subscriber
    ): void {
        $message->getData()->willReturn([
            'user_guid' => "1",
            'tenant_id' => 1,
            'invite_token' => null
        ]);

        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $subscriber->subscribe('100000000000000519')
            ->shouldNotBeCalled();

        $this->entitiesBuilderMock->single("1")
            ->shouldBeCalledOnce()
            ->willReturn($subscriber);

        $this->featuredEntityAutoSubscribeServiceMock->autoSubscribe($subscriber, 1)
            ->shouldBeCalledOnce();

        $this->emailAutoSubscribeServiceMock->subscribeToDefaultEmails(1)
            ->shouldBeCalledOnce();

        $this->processPostRegistrationEvent($message);
    }

    /**
     * @param Message $message
     * @param User $subscriber
     * @return void
     */
    public function it_should_process_post_registration_event_successfully_when_NO_tenant_WITH_invite(
        Message $message,
        User    $subscriber
    ): void {
        $message->getData()->willReturn([
            'user_guid' => "1",
            'tenant_id' => null,
            'invite_token' => "token"
        ]);

        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $subscriber->subscribe('100000000000000519')
            ->shouldBeCalledOnce();

        $this->entitiesBuilderMock->single("1")
            ->shouldBeCalledOnce()
            ->willReturn($subscriber);

        $this->featuredEntityAutoSubscribeServiceMock->autoSubscribe($subscriber, 1)
            ->shouldNotBeCalled();

        $this->inviteProcessorServiceMock->processInvite($subscriber, "token")
            ->shouldBeCalledOnce();

        $this->emailAutoSubscribeServiceMock->subscribeToDefaultEmails(1)
            ->shouldBeCalledOnce();

        $this->processPostRegistrationEvent($message);
    }

    /**
     * @param Message $message
     * @param User $subscriber
     * @return void
     */
    public function it_should_process_post_registration_event_successfully_when_WITH_tenant_WITH_invite(
        Message $message,
        User    $subscriber
    ): void {
        $message->getData()->willReturn([
            'user_guid' => "1",
            'tenant_id' => 1,
            'invite_token' => "token"
        ]);

        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $subscriber->subscribe('100000000000000519')
            ->shouldNotBeCalled();

        $this->entitiesBuilderMock->single("1")
            ->shouldBeCalledOnce()
            ->willReturn($subscriber);

        $this->featuredEntityAutoSubscribeServiceMock->autoSubscribe($subscriber, 1)
            ->shouldBeCalled();

        $this->inviteProcessorServiceMock->processInvite($subscriber, "token")
            ->shouldBeCalledOnce();

        $this->emailAutoSubscribeServiceMock->subscribeToDefaultEmails(1)
            ->shouldBeCalledOnce();

        $this->processPostRegistrationEvent($message);
    }
}
