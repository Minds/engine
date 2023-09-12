<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Emailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emailer as IssuerEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Types\GiftCardTarget;
use Minds\Entities\User;
use Minds\Helpers\Validation;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class EmailDelegate
{
    public function __construct(
        private readonly ?Emailer $recipientEmailer,
        private readonly ?IssuerEmailer $issuerEmailer,
        private readonly ?EntitiesBuilder $entitiesBuilder,
    ) {
    }

    /**
     * @param GiftCard $giftCard
     * @param string $recipient
     * @return void
     * @throws GraphQLException
     */
    public function onRecipientEmailRequested(
        GiftCard $giftCard,
        GiftCardTarget $recipient,
        User $sender
    ): void {
        if (!$recipient->targetEmail && !$recipient->targetUserGuid) {
            throw new GraphQLException('You must provide at least one between target email or target user guid', 400, null, 'Validation', ['field' => 'targetInput']);
        }
        if ($recipient->targetEmail && !Validation::isValidEmail($recipient->targetEmail)) {
            throw new GraphQLException('Recipient user guid or email must be provided', 400, null, 'Validation', ['field' => 'targetInput']);
        }

        $recipientUser = $this->entitiesBuilder->single($recipient->targetUserGuid) ?? null;

        $this->recipientEmailer
            ->setGiftCard($giftCard)
            ->setSender($sender)
            ->setUser($recipientUser)
            ->setTargetEmail($recipient->targetEmail ?? null)
            ->setTopic('gift-card-claim-email')
            ->send();
    }

    /**
     * Called when an issuer email is requested. Sends an email to the issuer.
     * @param GiftCard $giftCard - gift card.
     * @param User $issuer - issuer to send an email to.
     * @param string|null $paymentTxId - optional TXID allowing a receipt to be provided in the email.
     * @return void
     */
    public function onIssuerEmailRequested(
        GiftCard $giftCard,
        User $issuer,
        ?string $paymentTxId
    ): void {
        $this->issuerEmailer
            ->setGiftCard($giftCard)
            ->setSender(new SystemUser())
            ->setUser($issuer)
            ->setTargetEmail($issuer->getEmail())
            ->setTopic('gift-card-issuer-email')
            ->setPaymentTxId($paymentTxId)
            ->send();
    }
}
