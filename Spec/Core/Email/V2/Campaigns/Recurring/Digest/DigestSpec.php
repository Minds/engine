<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\Digest;

use Minds\Core\Email\V2\Campaigns\Recurring\Digest\Digest;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Discovery;
use Minds\Core\Discovery\Trend;
use Minds\Core\Notification;
use Minds\Entities\User;
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

    /** @var Discovery\Manager */
    protected $discoveryManager;

    /** @var Notification\Manager */
    protected $notificationManager;

    public function let(
        Template $template,
        Mailer $mailer,
        Manager $manager,
        Discovery\Manager $discoveryManager,
        Notification\Manager $notificationManager
    ) {
        $this->beConstructedWith($template, $mailer, $manager, $discoveryManager, $notificationManager);
        $this->discoveryManager = $discoveryManager;
        $this->notificationManager = $notificationManager;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(Digest::class);
    }

    public function it_should_build_digest_email_with_trends_and_notifications(User $user)
    {
        $this->setUser($user);

        //

        $this->discoveryManager->getTagTrends(Argument::any())
            ->willReturn([
                (new Trend)->setHashtag('music'),
                (new Trend)->setHashtag('beatles'),
                (new Trend)->setHashtag('60smusic'),
            ]);

        $this->discoveryManager->getPostTrends([ 'music', 'beatles', '60smusic' ], Argument::any())
            ->willReturn([]);

        //

        $this->notificationManager->setUser($user)
            ->willReturn($this->notificationManager);
        
        $this->notificationManager->getCount()
            ->willReturn(5);

        $this->build();
    }
}
