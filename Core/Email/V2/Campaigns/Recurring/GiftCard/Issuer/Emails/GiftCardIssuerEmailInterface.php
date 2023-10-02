<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails;

/**
 * Interface for emails. These classes contain various functions to aide in
 * building emails.
 */
interface GiftCardIssuerEmailInterface
{
    /**
     * Set the amount for the email.
     * @param float $amount - amount.
     * @return void
     */
    public function setAmount(float $amount): void;

    /**
     * Sets the body content for the email. Each array item is a paragraph.
     * @return array array of paragraphs for email body content.
     */
    public function buildBodyContentArray(): array;

    /**
     * Email subject.
     * @return string email subject.
     */
    public function buildSubject(): string;
}
