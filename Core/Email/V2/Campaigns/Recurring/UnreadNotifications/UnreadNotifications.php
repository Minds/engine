<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\UnreadNotifications;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Feeds;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications;
use Minds\Core\Notifications\Push\PushNotification;

class UnreadNotifications extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    /** @var Feeds\Elastic\Manager */
    protected $feedsManager;

    /** @var Notifications\Manager */
    protected $notificationManager;

    /** @var Logger */
    protected $logger;

    public function __construct(
        Template $template = null,
        Mailer $mailer = null,
        Manager $manager = null,
        Feeds\Elastic\Manager $feedsManager = null,
        Notifications\Manager $notificationManager = null,
        Logger $logger = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
        $this->feedsManager = $feedsManager ?? Di::_()->get('Feeds\Elastic\Manager');
        $this->notificationManager = $notificationManager ?? Di::_()->get('Notifications\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');

        $this->campaign = 'when';
        $this->topic = 'unread_notifications';
    }

    public function build(): ?Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'unread_notifications',
            'utm_medium' => 'email',
        ];

        $trackingQuery = http_build_query($tracking);
        $subject = 'You have unread notifications';

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('title', '');
        $this->template->set('hideGreeting', true);
        $this->template->set('signoff', 'Thank you,');
        $this->template->set('preheader', 'Some things you have missed');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('truncate', function ($text) {
            return \Minds\Helpers\Text::truncate($text ?: '');
        });

        //

        $unreadNotificationsCount = $this->notificationManager
            ->getUnreadCount($this->user);

        if ($unreadNotificationsCount === 0) {
            $this->logger->info("{$this->user->getGuid()} has no unread notifications");
            return null; // We cant send as there are no unread notifications
        }

        $this->template->set('unreadNotificationsCount', $unreadNotificationsCount);

        //

        $opts = new Notifications\NotificationsListOpts();
        $opts->setToGuid($this->user->getGuid())
            ->setLimit(12);

        $notifications = array_filter(iterator_to_array($this->notificationManager->getList($opts)), function ($item) {
            return $item[0]->getReadTimestamp() === null && $item[0]->getEntity();
        });

        // Of the above notifications, map to Push notifications so we can reuse their language

        $pushNotifications = array_filter(array_map(function ($item) {
            try {
                return new PushNotification($item[0]);
            } catch (\Exception $e) {
                $this->logger->info("{$this->user->getGuid()} " . $e->getMessage());
                return null;
            }
        }, $notifications));

        if (empty($pushNotifications)) {
            $this->logger->info("{$this->user->getGuid()} could not provided previews");
            return null;
        }

        $this->template->set('unreadPushNotifications', $pushNotifications);

        //

        $actionButton = (new ActionButton())
            ->setLabel('View all notifications')
            ->setPath('notifications/v3?'.http_build_query($tracking));

        $this->template->set('actionButton', $actionButton->build());

        //

        $message = new Message();
        $message->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [$this->user->getGuid(), sha1($this->user->getEmail()), sha1($this->campaign.$this->topic.time())]
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
                $this->saveCampaignLog();
                $this->mailer->send($message);
                $this->logger->info("{$this->user->getGuid()} sent");
            }
        } else {
            $this->logger->info("{$this->user->getGuid()} not able receive emails");
        }
    }
}
