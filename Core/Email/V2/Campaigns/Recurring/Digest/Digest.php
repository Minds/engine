<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\Digest;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Email\V2\Partials\SuggestedChannels\SuggestedChannels;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Di\Di;
use Minds\Core\Discovery;
use Minds\Core\Notification;

class Digest extends EmailCampaign
{
    use MagicAttributes;

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

    public function __construct(
        Template $template = null,
        Mailer $mailer = null,
        Manager $manager = null,
        Discovery\Manager $discoveryManager = null,
        Notification\Manager $notificationManager = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
        $this->discoveryManager = $discoveryManager ?? Di::_()->get('Discovery\Manager');
        $this->notificationManager = $notificationManager ?? Di::_()->get('Notification\Manager');

        $this->campaign = 'with';
        $this->topic = 'posts_missed_since_login';
    }

    public function build(): ?Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
        ];

        $trackingQuery = http_build_query($tracking);
        $subject = 'Your Minds Digest';

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('title', "Hi @{$this->user->username}");
        $this->template->set('hideGreeting', true);
        $this->template->set('signoff', 'Thank you,');
        $this->template->set('preheader', 'Some highlights from today');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);

        // Get trends (highlights) from discovery
        try {
            $tagTrends = $this->discoveryManager->getTagTrends([ 'limit' => 12 ]);
            $trends = $this->discoveryManager->getPostTrends(array_map(function ($trend) {
                return "{$trend->getHashtag()}";
            }, $tagTrends), [ 'limit' => 6 ]);
        } catch (Discovery\NoTagsException $e) {
            $trends = [
                [],
                [],
                [],
            ];
        } finally {
            $this->template->set('trends', $trends);
        }

        //

        $unreadNotificationsCount = $this->notificationManager
            ->setUser($this->user)
            ->getCount();

        $this->template->set('unreadNotificationsCount', $unreadNotificationsCount);

        //

        $hasDigestActivity = $unreadNotificationsCount > 0;
        $this->template->set('hasDigestActivity', $hasDigestActivity);

        if (!$hasDigestActivity || !count($trends)) {
            return null;
        }

        //

        $message = new Message();
        $message->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [$this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign.$this->topic.time())]
            ))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }

    public function send($time = null): void
    {
        $time = $time ?: time();
        //send email
        if ($this->canSend()) {
            $message = $this->build();
            if ($message) {
                $this->mailer->send($message);
            }
        }
    }
}
