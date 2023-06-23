<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

class Emailer extends EmailCampaign
{
    use MagicAttributes;

    private ?User $recipientUser = null;

    private ?GiftCard $giftCard = null;

    public function __construct(
        private readonly Template $template,
        private readonly Mailer $mailer,
        private readonly Logger $logger,
        $manager = null,
    ) {
        $this->manager = $manager;
        parent::__construct($manager);

        $this->campaign = "gift-card";
    }

    /**
     * @inheritDoc
     */
    public function send()
    {
        if (!$this->user?->email) {
            return;
        }

        $trackingQueryParams = [
            '__e_ct_guid' => $this->user->getGuid(),
            'campaign' => 'when',
            'topic' => $this->topic,
        ];

        $trackingQueryString = http_build_query($trackingQueryParams);

        $this->mailer->send($this->buildMessage());
        $this->saveCampaignLog();
    }

    private function buildMessage(): ?Message
    {
        if (!$this->topic || !$this->giftCard) return null;

        $this->template->set('user', $this->recipientUser);
        $this->template->set('username', $this->recipientUser->getUsername());
        $this->template->set('email', $this->recipientUser->getEmail());
        $this->template->set('guid', $this->recipientUser->getGuid());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQueryString);
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', $preHeaderText);
        $this->template->set('bodyText', $bodyText);
        $this->template->set('headerText', $headerText);
    }
}
