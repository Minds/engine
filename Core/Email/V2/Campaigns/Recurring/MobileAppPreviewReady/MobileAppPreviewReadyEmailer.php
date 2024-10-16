<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\MobileAppPreviewReady;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Di\Di;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileAppPreviewQRCodeService;

/**
 * Mobile app preview ready emailer.
 */
class MobileAppPreviewReadyEmailer extends EmailCampaign
{
    public function __construct(
        private Template $template,
        private readonly Mailer $mailer,
        private readonly Config $config,
        private readonly TenantTemplateVariableInjector $tenantTemplateVariableInjector,
        private readonly MobileAppPreviewQRCodeService $mobileAppPreviewQRCodeService,
        private readonly Logger $logger,
        $manager = null,
    ) {
        $this->manager = $manager ?? Di::_()->get('Email\Manager');
        parent::__construct($manager);

        $this->campaign = 'with';
        $this->topic = 'mobile_app_preview_ready';
    }

    /**
     * Build email.
     * @return Message|null Email message.
     */
    public function build(): ?Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'mobile_app_preview_ready',
            'utm_medium' => 'email',
        ];

        $siteUrl = $this->config->get('site_url');
        $tenantId = $this->config->get('tenant_id');

        if (!$tenantId) {
            $this->logger->error('Tenant ID not set');
            return null;
        }

        $trackingQuery = http_build_query($tracking);

        $subject = "Your mobile app preview is ready";

        $this->template->clear();

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('headerText', "Your mobile app preview is ready");
        $this->template->set('preheader', "Your mobile app preview is ready");
        $this->template->set('qrCodeImgSrc', "{$siteUrl}api/v3/multi-tenant/mobile-configs/qr-code");
        $this->template->set('mobileDeepLinkUrl', "{$siteUrl}api/v3/multi-tenant/mobile-configs/qr-code-link");
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);

        $this->template = $this->tenantTemplateVariableInjector->inject($this->template);

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
     * @return void
     */
    public function send(): void
    {
        if ($this->canSend()) {
            $message = $this->build();
            if ($message) {
                $this->saveCampaignLog();
                $this->mailer->send($message);
            }
        }
    }

    /**
     * Queue email sending.
     * @return void
     */
    public function queue(): void
    {
        if ($this->canSend()) {
            $message = $this->build();
            if ($message) {
                $this->saveCampaignLog();
                $this->mailer->queue($message);
            }
        }
    }
}
