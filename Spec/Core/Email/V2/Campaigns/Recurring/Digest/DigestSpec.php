<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\Digest;

use Minds\Core\Email\V2\Campaigns\Recurring\Digest\Digest;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Feeds;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Discovery\Trend;
use Minds\Core\Notification;
use Minds\Entities\User;
use Minds\Entities\Activity;
use Minds\Common\Repository\Response;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class DigestSpec extends ObjectBehavior
{
    protected Collaborator $managerMock;

    protected Collaborator $feedsManagerMock;

    /** @var Notification\Manager */
    protected Collaborator $notificationManagerMock;

    public function let(
        Template $template,
        Mailer $mailer,
        Manager $manager,
        Feeds\Elastic\V2\Manager $feedsManagerMock,
        Notification\Manager $notificationManagerMock,
    ) {
        $this->beConstructedWith($template, $mailer, $manager, $feedsManagerMock, $notificationManagerMock);
        $this->managerMock = $manager;
        $this->feedsManagerMock = $feedsManagerMock;
        $this->notificationManagerMock = $notificationManagerMock;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(Digest::class);
    }

    public function it_should_build_digest_email_with_trends_and_notifications(User $user)
    {
        $this->setUser($user);

        $user->getGuid()->willReturn('123');
        $user->getEmail()->willReturn('mark@minds.com');
        $user->get('username')->willReturn('mark');
        $user->get('name')->willReturn('mark');

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

        $this->build();
    }
}
