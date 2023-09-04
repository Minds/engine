<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts;

use Minds\Entities\User;

interface GiftCardProductInterface
{
    public function setAmount(float $amount): void;

    public function setSender(User $sender): void;

    public function buildContent(): string;

    public function buildSubject(): string;
}
