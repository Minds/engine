<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Entities\User;

/**
 * TenantTrialEmailer emailer.
 */
class TenantTrialEmailer extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    protected string $code;

    private int $tenantId;
    private bool $isTrial = true;
    private string $username;
    private string $password;

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
        $this->topic = 'tenant_trial';
    }

    public function setTenantId(int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function setIsTrial(bool $isTrial): self
    {
        $this->isTrial = $isTrial;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Send email via queue.
     * @return bool
     */
    public function send(): bool
    {
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
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'state' => 'new',
        ];

        $this->template->setLocale("en");

        if (!$siteName = $this->config->get('site_name')) {
            $siteName = 'Minds';
        }

        $subject = $this->isTrial ? "Your trial is ready" : 'Your network is ready';
        $link = "https://" . md5($this->tenantId) . '.networks.minds.com/login';

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.v2.tpl');

        $this->template->set('site_name', $siteName);
        $this->template->set('guid', $this->user->getGuid());
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('preheader', "Your trial is ready");
        $this->template->set('username', $this->username);
        $this->template->set('password', $this->password);

        $actionButton = (new ActionButtonV2())
            ->setLabel('Go to network')
            ->setPath($link);
        $this->template->set('actionButton', $actionButton->build());

        $helpButton = (new ActionButtonV2())
            ->setLabel('Pick a time')
            ->setPath('https://cal.com/minds/30-minute-meeting');
        $this->template->set('helpButton', $helpButton->build());

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
}
