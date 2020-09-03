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
use Prophecy\Argument;

class DigestSpec extends ObjectBehavior
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    /** @var Feeds\Elastic\Manager */
    protected $feedsManager;

    /** @var Notification\Manager */
    protected $notificationManager;

    public function let(
        Template $template,
        Mailer $mailer,
        Manager $manager,
        Feeds\Elastic\Manager $feedsManager,
        Notification\Manager $notificationManager
    ) {
        $this->beConstructedWith($template, $mailer, $manager, $feedsManager, $notificationManager);
        $this->manager = $manager;
        $this->feedsManager = $feedsManager;
        $this->notificationManager = $notificationManager;
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

        $this->manager->getCampaignLogs($user)
            ->shouldBeCalled()
            ->willReturn(new Response());

        //

        $this->feedsManager->getList([
            'subscriptions' => '123',
            'hide_own_posts' => true,
            'limit' => 12,
            'to_timestamp' => strtotime('7 days ago') * 1000,
            'algorithm' => \Minds\Core\Search\SortingAlgorithms\DigestFeed::class,
            'period' => 'all',
            'type' => 'activity',
        ])
            ->willReturn(new Response([
                (new FeedSyncEntity())
                    ->setEntity(new Activity())
            ]));

        //

        $this->notificationManager->setUser($user)
            ->willReturn($this->notificationManager);
        
        $this->notificationManager->getCount()
            ->willReturn(5);

        $this->build();
    }
}
