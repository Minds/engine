<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails\GiftCardIssuerEmailInterface;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails\PlusEmail;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails\ProEmail;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\Manager as PaymentManager;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;
use phpseclib3\Crypt\Random;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Emailer for gift card issuer emails. Containing a link for the issuer to send
 * to somebody, and the issuers payment receipt link.
 *
 * @method self setGiftCard(GiftCard $giftCard)
 * @method self setSender(User $sender)
 * @method self setTargetEmail(string $targetEmail)
 * @method self setUser(User $user)
 * @method self setTopic(string $topic)
 * @method self setPaymentTxId(string $paymentTxId)
 */
class Emailer extends EmailCampaign
{
    use MagicAttributes;

    /** Gift card we're sending the email for. */
    private ?GiftCard $giftCard = null;

    /** Sender of the email. */
    private ?User $sender = null;

    /** Email receiver. */
    private ?string $targetEmail = null;

    /** Payment TXID to link to a receipt. */
    private ?string $paymentTxId = null;

    public function __construct(
        private readonly Template $template,
        private readonly Mailer $mailer,
        private readonly PaymentManager $paymentManager,
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
     * Send the email.
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

        $this->mailer->send($this->buildMessage());

        $this->logger->warning('Gift card email sent', [$this->mailer->getStats(), $this->mailer->getErrors()]);
        $this->saveCampaignLog();
    }

    /**
     * Build the email content
     * @return Message|null built message.
     */
    private function buildMessage(): ?Message
    {
        if (!$this->topic || !$this->giftCard) {
            return null;
        }

        $email = $this->getEmail($this->giftCard->productId);
        $email->setAmount($this->giftCard->amount);

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');

        $this->template->set('user', $this->user ?? null);
        $this->template->set('email', $this->user?->getEmail() ?? $this->targetEmail);
        $this->template->set('guid', $this->user?->getGuid());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $this->getTrackingQueryString());
        $this->template->set('preheader', "Thanks for purchasing a Minds subscription gift.");
        $this->template->set('headerText', "Your gift is ready");
        $this->template->set('bodyContentArray', $email->buildBodyContentArray());
        $this->template->set('footerText', $this->buildFooterText());
        $this->template->set('claimLink', $this->buildClaimUrl());

        if ($this->paymentTxId) {
            $receiptUrl = $this->buildReceiptUrl();

            if ($receiptUrl) {
                $this->template->set('additionalCtaPath', $receiptUrl);
                $this->template->set('additionalCtaText', 'Receipt');
            }
        }

        return (new Message())
            ->setTo((new User())->setName("")->setEmail($this->targetEmail))
            ->setMessageId(
                implode(
                    '-',
                    [
                        Random::string(10),
                        sha1($this->targetEmail),
                        sha1($this->campaign . $this->topic . time())
                    ]
                )
            )
            ->setSubject($email->buildSubject())
            ->setHtml($this->template);
    }

    /**
     * Gets email builder for a given product id.
     * @param GiftCardProductIdEnum $productIdEnum - product id.
     * @return GiftCardIssuerEmailInterface - email builder.
     */
    private function getEmail(GiftCardProductIdEnum $productIdEnum): GiftCardIssuerEmailInterface
    {
        return match ($productIdEnum) {
            GiftCardProductIdEnum::PLUS => new PlusEmail(),
            GiftCardProductIdEnum::PRO => new ProEmail(),
            default => throw new GraphQLException("Invalid gift card product id: {$this->giftCard->productId}")
        };
    }

    /**
     * Builds common footer text.
     * @return string footer text.
     */
    private function buildFooterText(): string
    {
        return "<em>To copy and share, either right-click the link on a computer, or long-press the link on a mobile device.</em>";
    }

    /**
     * Build claim URL.
     * @return string claim URL.
     */
    private function buildClaimUrl(): string
    {
        $siteUrl = $this->mindsConfig->get('site_url') ?: 'https://www.minds.com/';
        return $siteUrl . "gift-cards/claim/{$this->giftCard->claimCode}";
    }

    /**
     * Build receipt URL.
     * @return string receipt URL.
     */
    private function buildReceiptUrl(): string
    {
        try {
            $payment = $this->paymentManager->getPaymentById(
                $this->paymentTxId
            );
            return $payment->getReceiptUrl() ?? '';
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Query string for tracking.
     * @return string query string.
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
