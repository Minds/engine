<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails\GiftCardIssuerEmailInterface;

/**
 * Emails helper for Minds+. Contain various functions to aide
 * in building Minds+ emails.
 */
class PlusEmail implements GiftCardIssuerEmailInterface
{
    public function __construct(private ?Config $config = null)
    {
        $this->config ??= Di::_()->get('Config');
    }

    private float $amount;

    /**
     * Set the amount for the email.
     * @param float $amount - amount.
     * @return void
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * Sets the body content for the email. Each array item is a paragraph.
     * @return array array of paragraphs for email body content.
     */
    public function buildBodyContentArray(): array
    {
        return [
            "Thanks for gifting <b>Minds+ {$this->getSpanText()}</b> to someone lucky. If you selected a recipient during checkout, we've already notified them with details on how they can claim the gift.",
            "<b>Or you can share this claim link</b> with them, whether or not they already have a Minds account."
        ];
    }

    /**
     * Email subject.
     * @return string email subject.
     */
    public function buildSubject(): string
    {
        return "Your Minds+ gift is on the way";
    }

    /**
     * Get timespan for subscription, in brackets,
     * derived from the gift card amount and most expensive tier
     * the user can purchase with it.
     * @return string timespan.
     */
    private function getSpanText(): string
    {
        $upgradesConfig = $this->config->get('upgrades');
        $yearlyUsd = $upgradesConfig['plus']['yearly']['usd'] ?? null;
        $monthlyUsd = $upgradesConfig['plus']['monthly']['usd'] ?? null;

        if (!$monthlyUsd || !$yearlyUsd) {
            return '';
        }

        if ($this->amount >= $yearlyUsd) {
            return "(1 year)";
        }

        if ($this->amount >= $monthlyUsd) {
            return "(1 month)";
        }

        return '';
    }
}
