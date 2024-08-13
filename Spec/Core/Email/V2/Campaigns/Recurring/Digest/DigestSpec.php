<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\Digest;

use Minds\Core\Email\V2\Campaigns\Recurring\Digest\Digest;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Feeds;
use Minds\Core\Notification;
use Minds\Entities\User;
use Minds\Entities\Activity;
use Minds\Common\Repository\Response;
use Minds\Core\Email\V2\Partials\UnreadMessages\UnreadMessagesPartial;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class DigestSpec extends ObjectBehavior
{
    protected Collaborator $templateMock;

    protected Collaborator $managerMock;

    protected Collaborator $feedsManagerMock;

    /** @var Notification\Manager */
    protected Collaborator $notificationManagerMock;

    protected Collaborator $unreadMessagesPartialMock;

    public function let(
        Template $templateMock,
        Mailer $mailer,
        Manager $manager,
        Feeds\Elastic\V2\Manager $feedsManagerMock,
        Notification\Manager $notificationManagerMock,
        UnreadMessagesPartial $unreadMessagesPartialMock,
    ) {
        $this->beConstructedWith($templateMock, $mailer, $manager, $feedsManagerMock, $notificationManagerMock, null, null, $unreadMessagesPartialMock);
        $this->templateMock = $templateMock;
        $this->managerMock = $manager;
        $this->feedsManagerMock = $feedsManagerMock;
        $this->notificationManagerMock = $notificationManagerMock;
        $this->unreadMessagesPartialMock = $unreadMessagesPartialMock;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(Digest::class);
    }

    public function it_should_build_digest_email_with_trends_notifications_and_unread_messages(
        User $user
    ) {
        $this->setUser($user);

        $user->getGuid()->willReturn('123');
        $user->getEmail()->willReturn('mark@minds.com');
        $user->get('username')->willReturn('mark');
        $user->get('name')->willReturn('mark');

        //

        $this->templateMock->clear()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->templateMock->setTemplate('default.v2.tpl')
            ->shouldBeCalled()
            ->willReturn($this->templateMock);

        $this->templateMock->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->templateMock->set(Argument::any(), Argument::any())
            ->shouldBeCalled();

        //

        $this->managerMock->getCampaignLogs($user)
            ->shouldBeCalled()
            ->willReturn(new Response());

        //

        $this->feedsManagerMock->getTop(Argument::any())
            ->willYield([new Activity()]);

        //

        $this->notificationManagerMock->setUser($user)
            ->willReturn($this->notificationManagerMock);
        
        $this->notificationManagerMock->getCount()
            ->willReturn(5);

        //

        $this->unreadMessagesPartialMock->withArgs($user, Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->unreadMessagesPartialMock);

        $this->unreadMessagesPartialMock->build()
            ->shouldBeCalled()
            ->willReturn('<div>Unread messages partial</div>');

        $this->build();
    }
}
