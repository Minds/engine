<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\ForgotPassword;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;

/**
 * Forgot password emailer.
 */
class ForgotPasswordEmailer extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    protected string $code;

    /**
     * @param Template $template
     * @param Mailer $mailer
     * @param Config $config
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
        $this->topic = 'confirmation';
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Build email message.
     * @return Message
     */
    public function build()
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'state' => 'new',
        ];

        $this->template->setLocale($this->user->getLanguage());

        $siteName = $this->config->get('site_name') ?? 'Minds';

        $subject = 'Password reset';
        $link = $this->config->get('site_url') . "forgot-password;username=" . $this->user->getUsername() . ";code=" . $this->code;

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.v2.tpl');

        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('site_name', $siteName);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('preheader', 'Reset your password by clicking this link.');
        $this->template->set('title', $subject);
        $this->template->set('headerText', 'Reset your password');
        $this->template->set('bodyText', 'Use this link to reset your password on ' . $siteName . '. If you did not request to reset your password, please disregard this message.');

        if ((bool) $this->config->get('tenant_id')) {
            $this->template->set('hide_unsubscribe_link', true);
            $this->template = $this->tenantTemplateVariableInjector->inject($this->template);
        }
        
        // Create action button
        $actionButton = (new ActionButtonV2())
            ->setLabel('Reset password')
            ->setPath($link);
    
        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message
            ->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [ $this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign . $this->topic . time()) ]
            ))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }

    /**
     * Send email via queue.
     * @return void
     */
    public function send()
    {
        if ($this->user && $this->user->getEmail()) {
            $this->mailer->queue(
                $this->build(),
                true
            );

            $this->saveCampaignLog();
        }
    }
}
