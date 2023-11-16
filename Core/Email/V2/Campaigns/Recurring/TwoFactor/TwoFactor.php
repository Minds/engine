<?php
/**
 * TwoFactor
 *
 * @author mark
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;

class TwoFactor extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    protected $code;

    /**
     * TwoFactor constructor.
     * @param Template $template
     * @param Mailer $mailer
     * @param Config|null $config
     * @param TenantTemplateVariableInjector|null
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

        $translator = $this->template->getTranslator();

        if(!$siteName = $this->config->get('site_name')) {
            $siteName = 'Minds';
        }

        $subject = $this->code . ' is your verification code for ' . $siteName;
        $previewText = 'Verify your email to get started';

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.v2.tpl');
        if ($this->user->isTrusted()) {
            $this->template->setBody('./template.v2.2fa.tpl');
            $previewText = "Verify your action";
        } else {
            $this->template->setBody('./template.v2.verify.tpl');
        }
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('site_name', $siteName);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('preheader', $previewText);
        $this->template->set('title', $subject);

        $this->template->set('code', $this->code);

        if ((bool) $this->config->get('tenant_id')) {
            $this->template->set('hide_unsubscribe_link', true);
            $this->template = $this->tenantTemplateVariableInjector->inject($this->template);
        }

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
     * @return void
     */
    public function send()
    {
        if ($this->user && $this->user->getEmail()) {
            $this->mailer->send(
                $this->build(),
                true
            );

            $this->saveCampaignLog();
        }
    }
}
