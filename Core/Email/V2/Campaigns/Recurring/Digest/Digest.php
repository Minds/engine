<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\Digest;

use DateTime;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Feeds\Elastic\V2\Manager as ElasticFeedManager;
use Minds\Core\Di\Di;
use Minds\Core\Feeds;
use Minds\Core\Notification;
use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Discovery\NoTagsException;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Search\SortingAlgorithms;

class Digest extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    protected ElasticFeedManager $elasticFeedManager;

    /** @var Notification\Manager */
    protected $notificationManager;

    public function __construct(
        Template $template = null,
        Mailer $mailer = null,
        Manager $manager = null,
        ElasticFeedManager $elasticFeedManager = null,
        Notification\Manager $notificationManager = null,
        protected ?Config $config = null,
        protected ?TenantTemplateVariableInjector $tenantTemplateVariableInjector= null,
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
        $this->elasticFeedManager = $elasticFeedManager ?? Di::_()->get(ElasticFeedManager::class);
        $this->notificationManager = $notificationManager ?? Di::_()->get('Notification\Manager');
        $this->config ??= Di::_()->get(Config::class);
        $this->tenantTemplateVariableInjector ??= Di::_()->get(TenantTemplateVariableInjector::class);

        $this->campaign = 'with';
        $this->topic = 'posts_missed_since_login';
    }

    public function build(): ?Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'digest',
            'utm_medium' => 'email',
        ];

        $trackingQuery = http_build_query($tracking);

        if(!$siteName = $this->config->get('site_name')) {
            $siteName = 'Minds';
        }
    
        $subject = "What's happening on $siteName";

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('headerText', $subject);
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

        if ((bool) $this->config->get('tenant_id')) {
            $this->template = $this->tenantTemplateVariableInjector->inject($this->template);
        }

        // Create action button
        $actionButton = (new ActionButtonV2())
            ->setLabel($this->config->get('tenant_id') ? 'Go to network' : 'Go to Minds')
            ->setPath($this->config->get('site_url') . "?$trackingQuery&utm_content=cta");
    
        $this->template->set('actionButton', $actionButton->build());

        // Get the campaign logs for this user
        /** @var Response */
        $campaigns = $this->manager
            ->getCampaignLogs($this->user)
            ->filter(function ($campaignLog) {
                return $campaignLog->getEmailCampaignId() === $this->getEmailCampaignId();
            })
            ->sort(function ($a, $b) {
                return $a->getTimeSent() <=> $b->getTimeSent();
            });

        // Get the timestamp of the last sent campaign
        $refUnixTimestamp = max(isset($campaigns[0]) ? $campaigns[0]->getTimeSent() : 0, strtotime('14 days ago'));


        // Get trends (highlights) from discovery
        try {
            $activities = iterator_to_array($this->elasticFeedManager->getTop(
                new QueryOpts(
                    user: $this->user,
                    onlySubscribedAndGroups: true,
                    olderThan: (new DateTime)->setTimestamp($refUnixTimestamp),
                )
            ));
        } catch (\Exception $e) {
            return null;
        } finally {
            $this->template->set('activities', $activities ?? []);
        }

        //

        if (!count($activities)) {
            return null; // Require activies to be set in order for this email to send
        }

        //

        $unreadNotificationsCount = $this->notificationManager
            ->setUser($this->user)
            ->getCount();

        $this->template->set('unreadNotificationsCount', $unreadNotificationsCount);

        //

        $hasDigestActivity = $unreadNotificationsCount > 0;
        $this->template->set('hasDigestActivity', $hasDigestActivity);

        if (!$hasDigestActivity && !count($activities)) {
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
                $this->mailer->send($message);
            }
        }
    }
}
