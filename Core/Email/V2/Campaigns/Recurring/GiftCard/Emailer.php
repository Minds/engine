<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts\BoostCredit;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts\GiftCardProductInterface;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;
use phpseclib3\Crypt\Random;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * @method self setGiftCard(GiftCard $giftCard)
 * @method self setSender(User $sender)
 * @method self setTargetEmail(string $targetEmail)
 */
class Emailer extends EmailCampaign
{
    use MagicAttributes;

    private ?GiftCard $giftCard = null;

    private ?User $sender = null;

    private ?string $targetEmail = null;

    public function __construct(
        private readonly Template $template,
        private readonly Mailer $mailer,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Config $mindsConfig,
        private readonly Logger $logger,
        $manager = null,
    ) {
        $this->manager = $manager ?? Di::_()->get('Email\Manager');
        parent::__construct($manager);

        $this->campaign = "gift-card";
    }

    /**
     * @inheritDoc
     */
    public function send()
    {
        if (!$this->targetEmail && !$this->user?->email) {
            return;
        }

        if (!$this->sender) {
            $this->sender = $this->entitiesBuilder->single($this->giftCard->issuedByGuid);
            if (!$this->sender) {
                return;
            }
        }

        $this->mailer->send($this->buildMessage($this->sender));

        $this->logger->warning('Gift card email sent', [$this->mailer->getStats(), $this->mailer->getErrors()]);
        $this->saveCampaignLog();
    }

    private function buildMessage(User $sender): ?Message
    {
        if (!$this->topic || !$this->giftCard) {
            return null;
        }

        $productHandler = $this->getProductHandler($this->giftCard->productId);
        $productHandler->setAmount($this->giftCard->amount);
        $productHandler->setSender($sender);

        $bodyText = $productHandler->buildContent();

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');

        $this->template->set('user', $this->user ?? null);
        $this->template->set('username', $this->user?->getUsername());
        $this->template->set('email', $this->user?->getEmail() ?? $this->targetEmail);
        $this->template->set('guid', $this->user?->getGuid());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $this->getTrackingQueryString());
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', "Claim your gift card");
        $this->template->set('bodyText', $bodyText);
        $this->template->set('headerText', "You received a gift");

        $siteUrl = $this->mindsConfig->get('site_url') ?: 'https://www.minds.com/';
        $this->template->set('signupPath', $siteUrl . "register");

        $actionButton = (new ActionButtonV2())
            ->setLabel("Claim gift")
            ->setPath($siteUrl . "gift-cards/claim/{$this->giftCard->claimCode}");

        $this->template->set('actionButton', $actionButton->build());

        return (new Message())
            ->setTo($this->user ?? (new User())->setName("")->setEmail($this->targetEmail))
            ->setMessageId(
                implode(
                    '-',
                    [
                        $this->user?->getGuid() ?? Random::string(10),
                        sha1($this->user?->getEmail() ?? $this->targetEmail),
                        sha1($this->campaign . $this->topic . time())
                    ]
                )
            )
            ->setSubject($productHandler->buildSubject())
            ->setHtml($this->template);
    }

    /**
     * @param GiftCardProductIdEnum $productIdEnum
     * @return GiftCardProductInterface
     */
    private function getProductHandler(GiftCardProductIdEnum $productIdEnum): GiftCardProductInterface
    {
        return match ($productIdEnum) {
            GiftCardProductIdEnum::BOOST => new BoostCredit(),
            default => throw new GraphQLException("Invalid gift card product id: {$this->giftCard->productId}")
        };
    }

    /**
     * @return string
     */
    private function getTrackingQueryString(): string
    {
        return http_build_query(
            [
                '__e_ct_guid' => $this->user?->getGuid(),
                '__e_ct_email' => $this->user?->getEmail() ?? $this->targetEmail,
                'campaign' => 'when',
                'topic' => $this->topic,
            ]
        );
    }
}
