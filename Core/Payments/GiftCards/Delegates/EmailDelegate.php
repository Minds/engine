<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Delegates;

use InvalidArgumentException;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Emailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\GiftCards\Models\GiftCard;

class EmailDelegate
{
    public function __construct(
        private readonly ?Emailer $emailer,
        private readonly ?EntitiesBuilder $entitiesBuilder,
    ) {
    }

    /**
     * @param GiftCard $giftCard
     * @param int|null $recipientUserGuid
     * @param string|null $recipientEmail
     * @return void
     */
    public function onCreateGiftCard(
        GiftCard $giftCard,
        ?int $recipientUserGuid,
        ?string $recipientEmail,
    ): void {
        if (!$recipientUserGuid && !$recipientEmail) {
            throw new InvalidArgumentException('Recipient user guid or email must be provided');
        }
        $recipientUser = $this->entitiesBuilder->single($recipientUserGuid) ?? null;

        $this->emailer
            ->setGiftCard($giftCard)
            ->setUser($recipientUser)
            ->setTargetEmail($recipientEmail)
            ->setTopic('gift-card-claim-email')
            ->send();
    }
}
