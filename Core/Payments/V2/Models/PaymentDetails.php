<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Models;

use Exception;
use Minds\Core\Guid;
use Minds\Entities\ExportableInterface;

class PaymentDetails implements ExportableInterface
{
    public int $paymentGuid;
    public int $userGuid;
    public ?int $affiliateUserGuid = null;
    public int $paymentType;
    public int $paymentStatus;
    public int $paymentMethod;
    public int $paymentAmountMillis;
    public ?int $refundedAmountMillis = null;
    public ?bool $isCaptured = false;
    public ?string $paymentTxId = null;
    public int $createdTimestamp;
    public ?int $updatedTimestamp = null;

    private const READONLY_PROPS = [
        'paymentGuid',
    ];

    private array $changedProps = [];

    public function __construct(array ...$props)
    {
        if (count($props) === 1 and isset($props[0])) {
            $props = $props[0];
        }

        foreach ($props as $prop => $value) {
            if (!property_exists($this, $prop)) {
                continue;
            }
            $this->$prop = $value;
        }

        if (!isset($this->paymentGuid)) {
            $this->paymentGuid = (int) Guid::build();
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function __set(string $name, mixed $value): void
    {
        if (!property_exists($this, $name)) {
            throw new Exception("");
        }

        if (in_array($name, self::READONLY_PROPS)) {
            throw new Exception("Property $name cannot be updated");
        }

        if (!in_array($name, $this->changedProps)) {
            $this->changedProps[] = $name;
        }

        $this->$name = $value;
    }

    public function resetChangedProps(): void
    {
        $this->changedProps = [];
    }

    public function export(array $extras = []): array
    {
        return [
            'payment_guid' => $this->paymentGuid,
            'user_guid' => $this->userGuid,
            'affiliate_user_guid' => $this->affiliateUserGuid,
            'payment_type' => $this->paymentType,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'payment_amount_millis' => $this->paymentAmountMillis,
            'refunded_amount_millis' => $this->refundedAmountMillis,
            'is_captured' => $this->isCaptured,
            'payment_tx_id' => $this->paymentTxId,
            'created_timestamp' => $this->createdTimestamp,
            'updated_timestamp' => $this->updatedTimestamp
        ];
    }
}
