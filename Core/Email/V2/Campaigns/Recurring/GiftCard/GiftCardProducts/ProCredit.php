<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts;

use Minds\Entities\User;

/**
 * Builds fields for Minds Pro credit email.
 */
class ProCredit implements GiftCardProductInterface
{
    private float $amount;
    private User $sender;

    /**
     * Set the amount of credits.
     * @param float $amount - amount.
     * @return void
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * Set the sender of the gift.
     * @param User $sender - sender.
     * @return void
     */
    public function setSender(User $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * Build content for email.
     * @return string content for the email.
     */
    public function buildContent(): string
    {
        return "You've been gifted <b>\$" . "{$this->amount} in Minds Pro Credits</b> by <b>{$this->sender->getName()}</b> to use towards any Minds Pro subscription you purchase!";
    }

    /**
     * Build the subject for the email.
     * @return string
     */
    public function buildSubject(): string
    {
        return "You've been gifted \$" . "{$this->amount} in Minds Pro Credits by {$this->sender->getName()}";
    }
}
