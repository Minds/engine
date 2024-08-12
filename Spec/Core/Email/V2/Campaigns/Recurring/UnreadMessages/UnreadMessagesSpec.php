<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages;

use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Entities\User;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages\UnreadMessages;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\UnreadMessages\UnreadMessagesPartial;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class UnreadMessagesSpec extends ObjectBehavior
{
    protected Collaborator $templateMock;
    protected Collaborator $mailerMock;
    protected Collaborator $managerMock;
    protected Collaborator $configMock;
    protected Collaborator $tenantTemplateVariableInjectorMock;
    protected Collaborator $unreadMessagesPartialMock;

    public function let(
        Template $templateMock,
        Mailer $mailerMock,
        Manager $managerMock,
        Config $configMock,
        TenantTemplateVariableInjector $tenantTemplateVariableInjectorMock,
        UnreadMessagesPartial $unreadMessagesPartialMock,
    ) {
        $this->beConstructedWith($templateMock, $mailerMock, $managerMock, $configMock, $tenantTemplateVariableInjectorMock, $unreadMessagesPartialMock);
        $this->templateMock = $templateMock;
        $this->mailerMock = $mailerMock;
        $this->managerMock = $managerMock;
        $this->configMock = $configMock;
        $this->tenantTemplateVariableInjectorMock = $tenantTemplateVariableInjectorMock;
        $this->unreadMessagesPartialMock = $unreadMessagesPartialMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UnreadMessages::class);
    }

    public function it_should_build_an_unread_message_email(User $user)
    {
        $this->setUser($user);

        $user->getGuid()->willReturn('123');
        $user->getEmail()->willReturn('mark@minds.com');
        $user->get('username')->willReturn('mark');
        $user->get('name')->willReturn('mark');

        //

        $this->unreadMessagesPartialMock->withArgs($user, Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->unreadMessagesPartialMock);

        $this->unreadMessagesPartialMock->build()
            ->shouldBeCalled()
            ->willReturn('<div>Unread messages partial</div>');

        $this->build()->shouldBeAnInstanceOf(Message::class);
    }
}
