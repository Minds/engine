<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Email\V2\Partials\UnreadMessages\UnreadMessagesPartial;
use Minds\Entities\User;

/**
 * Unread messages email campaign.
 */
class UnreadMessages extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    /** @var int */
    protected $createdAfterTimestamp = 0;

    public function __construct(
        Template $template = null,
        Mailer $mailer = null,
        Manager $manager = null,
        protected ?Config $config = null,
        protected ?TenantTemplateVariableInjector $tenantTemplateVariableInjector= null,
        protected ?UnreadMessagesPartial $unreadMessagesPartial = null,
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
        $this->config ??= Di::_()->get(Config::class);
        $this->tenantTemplateVariableInjector ??= Di::_()->get(TenantTemplateVariableInjector::class);
        $this->unreadMessagesPartial ??= Di::_()->get(UnreadMessagesPartial::class);
        $this->campaign = 'with';
        $this->topic = 'posts_missed_since_login';
    }

    /**
     * Clone a new instance of the class with the given arguments.
     * @param User $user - user entity
     * @param integer $createdAfterTimestamp - created after timestamp
     * @return self - new instance of the class with the given arguments.
     */
    public function withArgs(User $user, int $createdAfterTimestamp): self
    {
        $instance = clone $this;
        $instance->user = $user;
        $instance->createdAfterTimestamp = $createdAfterTimestamp;
        return $instance;
    }

    /**
     * Build email
     * @return Message|null - built email message or null.
     */
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
    
        $subject = "Here's what you missed on $siteName";

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('headerText', $subject);
        $this->template->set('hideGreeting', true);
        $this->template->set('preheader', $subject);
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

        $unreadMessagesPartial = $this->unreadMessagesPartial->withArgs(
            user: $this->user,
            createdAfterTimestamp: (int) $this->createdAfterTimestamp ?? strtotime('-24 hours')
        )->build();

        if (!$unreadMessagesPartial) {
            return null;
        }

        $this->template->set('unreadMessagesPartial', $unreadMessagesPartial);

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

    /**
     * Send email.
     * @param int $time
     * @return void
     */
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
