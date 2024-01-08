<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\Invite;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Entities\User;

/**
 * Forgot password emailer.
 */
class InviteEmailer extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    protected string $code;

    private ?User $sender = null;
    private ?Invite $invite = null;

    /**
     * @param null $template
     * @param null $mailer
     * @param Config|null $config
     * @param TenantTemplateVariableInjector|null $tenantTemplateVariableInjector
     */
    public function __construct(
        $template = null,
        $mailer = null,
        private ?Config $config = null,
        private ?TenantTemplateVariableInjector $tenantTemplateVariableInjector = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->config ??= Di::_()->get(Config::class);
        $this->tenantTemplateVariableInjector ??= Di::_()->get(TenantTemplateVariableInjector::class);

        $this->campaign = 'global';
        $this->topic = 'signup_invite';
    }

    public function setSender(User $user): self
    {
        $this->sender = $user;
        return $this;
    }

    public function setInvite(Invite $invite): self
    {
        $this->invite = $invite;
        return $this;
    }

    /**
     * Send email via queue.
     * @return bool
     */
    public function send(): bool
    {
        if (!$this->sender || !$this->invite) {
            return false;
        }

        $this->mailer->send(
            $this->build()
        );
        $this->saveCampaignLog();

        return $this->mailer->getErrors() === "";
    }

    /**
     * Build email message.
     * @return Message
     */
    private function build(): Message
    {
        $tracking = [
            '__e_ct_invite_token' => $this->invite->inviteToken,
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'state' => 'new',
        ];

        $this->template->setLocale("en");

        if (!$siteName = $this->config->get('site_name')) {
            $siteName = 'Minds';
        }

        $subject = ($this->invite->status !== InviteEmailStatusEnum::PENDING ? "Reminder: " : '') . "You're invited to join $siteName";
        $link = $this->config->get('site_url') . "register?invite_token={$this->invite->inviteToken}";

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.v2.tpl');

        $this->template->set('site_name', $siteName);
        $this->template->set('guid', $this->sender->getGuid());
        $this->template->set('email', $this->invite->email);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('preheader', ($this->invite->status !== InviteEmailStatusEnum::PENDING ? "Reminder: " : '') . "Join $siteName by clicking this link.");
        $this->template->set('title', $subject);
        $this->template->set('headerText', $subject);
        $this->template->set('bodyText', "{$this->sender->getName()} sent you this personalized invite link. Join them and others on $siteName today.");
        $this->template->set('customMessage', $this->invite->bespokeMessage);

        if ((bool)$this->config->get('tenant_id')) {
            $this->template->set('hide_unsubscribe_link', true);
            $this->template = $this->tenantTemplateVariableInjector->inject($this->template);
        }

        // Create action button
        $actionButton = (new ActionButtonV2())
            ->setLabel('Join now')
            ->setPath($link);

        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message->to[] = [
            'name' => $this->invite->email,
            'email' => $this->invite->email,
        ];
        $message
            ->setMessageId(implode(
                '-',
                [$this->invite->inviteId, sha1($this->invite->email), sha1($this->campaign . $this->topic . time())]
            ))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }
}
