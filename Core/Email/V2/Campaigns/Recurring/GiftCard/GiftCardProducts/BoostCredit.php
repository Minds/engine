<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts;

use Minds\Entities\User;

class BoostCredit implements GiftCardProductInterface
{
    private float $amount;
    private User $sender;

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function setSender(User $sender): void
    {
        $this->sender = $sender;
    }

    public function buildContent(): string
    {
        return "You've been gifted <b>\${$this->amount} in Boost Credits</b> by <b>{$this->sender->getName()}</b> to use towards any future Boosts you make!";
    }

    public function buildSubject(): string
    {
        return "You've been gifted \${$this->amount} in Boost Credits by {$this->sender->getName()}";
    }
}
