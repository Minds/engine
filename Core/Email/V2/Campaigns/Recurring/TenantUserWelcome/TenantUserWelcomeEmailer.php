<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Di\Di;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;

/**
 * Tenant welcome emailer. Generates a welcome email for tenant users.
 */
class TenantUserWelcomeEmailer extends EmailCampaign
{
    public function __construct(
        private Template $template,
        private readonly Mailer $mailer,
        private readonly Config $config,
        private readonly TenantTemplateVariableInjector $tenantTemplateVariableInjector,
        private readonly SiteMembershipReaderService $siteMembershipReaderService,
        private readonly FeaturedEntityService $featuredEntityService,
        $manager = null,
    ) {
        $this->manager = $manager ?? Di::_()->get('Email\Manager');
        parent::__construct($manager);

        $this->campaign = 'with';
        $this->topic = 'welcome';
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
            'utm_campaign' => 'welcome',
            'utm_medium' => 'email',
        ];

        $trackingQuery = http_build_query($tracking);

        if (!$siteName = $this->config->get('site_name')) {
            $siteName = 'Minds';
        }
    
        $subject = "Welcome to $siteName";

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('headerText', "Welcome!");
        $this->template->set('bodyText', "Thanks for joining $siteName. Here's what you can do next to get the most out of the community.");
        $this->template->set('preheader', "Thanks for joining $siteName");
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
            ->setLabel("See what's new")
            ->setPath($this->config->get('site_url') . "?$trackingQuery&utm_content=cta");

        $this->template->set('actionButton', $actionButton->build());

        // Get memberships

        $siteMemberships = $this->siteMembershipReaderService->getSiteMemberships();
        $siteMembershipContainers = [];

        foreach ($siteMemberships as $siteMembership) {
            $siteMembershipContainers[] = [
                'name' => $siteMembership->membershipName,
                'description' => $siteMembership->membershipDescription,
                'pricingLabel' => $this->getMembershipPriceLabel($siteMembership),
                'actionButton' => (clone new ActionButtonV2())
                    ->setLabel('Join membership')
                    ->setPath($this->getJoinMembershipUrl($siteMembership))
                    ->build()
            ];
        }

        $this->template->set('site_membership_containers', $siteMembershipContainers);

        // Get groups

        $featuredGroups = $this->featuredEntityService->getFeaturedEntities(
            type: FeaturedEntityTypeEnum::GROUP,
            loadAfter: 0,
            limit: 3
        );

        $featuredGroupContainers = [];

        foreach ($featuredGroups->getEdges() as $featuredGroupEdge) {
            $featuredGroup = $featuredGroupEdge->getNode();
            $featuredGroupContainers[] = [
                'name' => $this->ellipsisTrim($featuredGroup->getName(), 36),
                'description' => $this->ellipsisTrim($featuredGroup->briefDescription, 250),
                'avatar_url' => $this->getAvatarUrl($featuredGroup->entityGuid),
                'join_url' => $this->getGroupUrl($featuredGroup->entityGuid)
            ];
        }

        $this->template->set('featured_group_containers', $featuredGroupContainers);

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
        if ($this->canSend() && (bool) $this->config->get('tenant_id')) {
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
        if ($this->canSend() && (bool) $this->config->get('tenant_id')) {
            $message = $this->build();
            if ($message) {
                $this->saveCampaignLog();
                $this->mailer->queue($message);
            }
        }
    }

    /**
     * Get URL to join membership.
     * @param SiteMembership $siteMembership Site membership.
     * @return string - Join membership URL.
     */
    private function getJoinMembershipUrl(SiteMembership $siteMembership): string
    {
        if ($siteMembership->purchaseUrl) {
            return $siteMembership->purchaseUrl;
        }
        return $this->config->get('site_url') . "api/v3/payments/site-memberships/$siteMembership->membershipGuid/checkout?redirectPath=/";
    }

    /**
     * Get membership pricing label.
     * @param SiteMembership $siteMembership - Site membership.
     * @return string - Membership pricing label.
     */
    private function getMembershipPriceLabel(SiteMembership $siteMembership): string
    {
        $price = $siteMembership->membershipPriceInCents / 100;

        switch($siteMembership->membershipPricingModel) {
            case SiteMembershipPricingModelEnum::ONE_TIME:
                return "$$price / one-time";
            case SiteMembershipPricingModelEnum::RECURRING:
                if ($siteMembership->membershipBillingPeriod === SiteMembershipBillingPeriodEnum::YEARLY) {
                    return "$$price / year";
                }
                return "$$price / month";
            default:
                return "$$price";
        }
    }

    /**
     * Get group URL.
     * @param integer $groupGuid - Group GUID.
     * @return string - Group URL.
     */
    private function getGroupUrl(int $groupGuid): string
    {
        return $this->config->get('site_url') . "group/$groupGuid";
    }

    /**
     * Get avatar URL.
     * @param integer $groupGuid - Group GUID.
     * @return string - Avatar URL.
     */
    private function getAvatarUrl(int $groupGuid): string
    {
        return $this->config->get('site_url') . "fs/v1/avatars/$groupGuid/large/" . time();
    }

    /**
     * Trim text and add ellipsis if too long.
     * @param string $text - Text to trim.
     * @param integer $limit - Limit.
     * @return string - Trimmed text.
     */
    private function ellipsisTrim(string $text, int $limit): string
    {
        if (!strlen($text)) {
            return "";
        }

        return mb_strlen($text) > $limit ?
            mb_substr($text, 0, ($limit - 3)) . '...' :
            $text ?? '';
    }
}
