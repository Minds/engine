<?php

declare(strict_types=1);

namespace Minds\Core\Payments\Models;

use Minds\Entities\ExportableInterface;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Model representing payments.
 * @method string getStatus()
 * @method self setStatus(string $status)
 * @method string getCurrency()
 * @method self setCurrency(string $currency)
 * @method string|null getStatementDescriptor()
 * @method self setStatementDescriptor(string $statementDescriptor)
 * @method int getMinorUnitAmount()
 * @method self setMinorUnitAmount(int $minorUnitAmount)
 * @method int getCreatedTimestamp()
 * @method self setCreatedTimestamp(int $createdTimestamp)
 * @method string|null getReceiptUrl()
 * @method self setReceiptUrl(string $receiptUrl)
 * @method string|null getPaymentId()
 * @method self setPaymentId(string $paymentId)
 * @method User|null getRecipient()
 * @method self setRecipient(User $user)
 * @method User|null getSender()
 * @method self setSender(User $user)
 */
class Payment implements ExportableInterface
{
    use MagicAttributes;

    /** @var string */
    private string $status;

    /** @var string */
    private string $currency;

    /** @var string|null */
    private ?string $statementDescriptor = null;

    /** @var int */
    private int $minorUnitAmount;

    /** @var int */
    private int $createdTimestamp;

    /** @var string|null */
    private ?string $receiptUrl = null;

    /** @var string|null */
    private ?string $paymentId = null;

    /** @var User|null */
    private ?User $recipient = null;

    /** @var User|null */
    private ?User $sender = null;
    
    /**
     * Construct from data from Stripe API.
     * @param array $data - data to construct from.
     * @return self new instance.
     */
    public static function fromData(array $data): self
    {
        $instance = new self;
        if ($data['status']) {
            $instance->setStatus($data['status']);
        }
        if ($data['payment_id']) {
            $instance->setPaymentId($data['payment_id']);
        }
        if ($data['currency']) {
            $instance->setCurrency($data['currency']);
        }
        if ($data['minor_unit_amount']) {
            $instance->setMinorUnitAmount($data['minor_unit_amount']);
        }
        if (isset($data['statement_descriptor'])) {
            $instance->setStatementDescriptor($data['statement_descriptor']);
        }
        if ($data['receipt_url']) {
            $instance->setReceiptUrl($data['receipt_url']);
        }
        if ($data['created_timestamp']) {
            $instance->setCreatedTimestamp($data['created_timestamp']);
        }
        if ($data['recipient']) {
            $instance->setRecipient($data['recipient']);
        }
        if ($data['sender']) {
            $instance->setSender($data['sender']);
        }
        return $instance;
    }

    /**
     * Export data for clients.
     * @param array $extras - items to append to export array.
     * @return array - exported Payment.
     */
    public function export(array $extras = []): array
    {
        $recipient = null;
        if ($this->getRecipient()) {
            $recipient = $this->getRecipient()->export() ?? [];
        }

        $sender = null;
        if ($this->getSender()) {
            $sender = $this->getSender()->export() ?? [];
        }

        return [
            'status' => $this->getStatus(),
            'payment_id' => $this->getPaymentId(),
            'currency' => $this->getCurrency(),
            'minor_unit_amount' => $this->getMinorUnitAmount(),
            'statement_descriptor' => $this->getStatementDescriptor(),
            'receipt_url' => $this->getReceiptUrl(),
            'created_timestamp' => $this->getCreatedTimestamp(),
            'recipient' => $recipient,
            'sender' => $sender,
            ...$extras
        ];
    }
}
