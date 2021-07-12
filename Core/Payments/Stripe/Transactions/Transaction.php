<?php
namespace Minds\Core\Payments\Stripe\Transactions;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

class Transaction
{
    use MagicAttributes;

    /** @var string $id */
    private $id;

    /** @var string */
    private $type;

    /** @var int $timestamp */
    private $timestamp;
 
    /** @var int $gross */
    private $gross;

    /** @var string $currency */
    private $currency;

    /** @var int $fees */
    private $fees;

    /** @var int $net */
    private $net;

    /** @var string $customerGuid */
    private $customerUserGuid;

    /** @var User $customerUser */
    private $customerUser;

    /** @var string */
    private $status;

    /** @var string */
    private $userGuid;

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export(array $extend = []) : array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'timestamp_iso8601' => date('c', $this->timestamp),
            'gross' => $this->gross,
            'currency' => $this->currency,
            'fees' => $this->fees,
            'net' => $this->net,
            'customer_user_guid' => $this->userGuid,
            'customer_user' => $this->customerUser ? $this->customerUser->export() : null,
        ];
    }
}
