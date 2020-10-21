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
use Minds\Core\Feeds;
use Minds\Core\Notification;
use Minds\Common\Repository\Response;
use Minds\Core\Search\SortingAlgorithms;

class PlusDigest extends EmailCampaign
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

    /** @var Notification\Manager */
    protected $notificationManager;

    public function __construct(
        Template $template = null,
        Mailer $mailer = null,
        Manager $manager = null,
        Feeds\Elastic\Manager $feedsManager = null,
        Notification\Manager $notificationManager = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
        $this->feedsManager = $feedsManager ?? Di::_()->get('Feeds\Elastic\Manager');
        $this->notificationManager = $notificationManager ?? Di::_()->get('Notification\Manager');

        $this->campaign = 'with';
        $this->topic = 'top_posts';
    }

    public function build(): ?Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'digest_plus',
            'utm_medium' => 'email',
        ];

        $trackingQuery = http_build_query($tracking);
        $subject = 'Top Minds+ posts of the week';

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('title', "Hi @{$this->user->username}");
        $this->template->set('hideGreeting', true);
        $this->template->set('signoff', 'Thank you,');
        $this->template->set('preheader', 'Some highlights of Minds+ from the past week');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('digestVariant', 'plus');

        // Get the campaign logs for this user

        // Get the timestamp of the last sent campaign
        $refUnixTimestamp = strtotime('7 days ago');

        // Get trends (highlights) from discovery
        try {
            $activities = $this->feedsManager->getList([
                'plus' => true,
                'hide_own_posts' => true,
                'limit' => 12,
                'to_timestamp' => $refUnixTimestamp * 1000,
                'algorithm' => SortingAlgorithms\PlusFeed::class,
                'period' => 'all',
                'type' => 'activity',
            ])
            ->map(function ($feedItem) {
                return $feedItem->getEntity();
            })
            ->filter(function ($activity) {
                return !$activity->remind_object;
            })
            ->toArray();
        } catch (Discovery\NoTagsException $e) {
            $activities = [];
        } catch (\Exception $e) {
            return false;
        } finally {
            $this->template->set('activities', $activities);
        }

        //

        if (!count($activities)) {
            return null; // Require activies to be set in order for this email to send
        }

        //

        $this->template->set('hasDigestActivity', false);

        if (!count($activities)) {
            return null;
        }

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
                $this->mailer->queue($message);
            }
        }
    }
}
