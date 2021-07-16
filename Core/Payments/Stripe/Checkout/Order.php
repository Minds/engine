<?php
/**
 * Stripe Order
 */
namespace Minds\Core\Payments\Stripe\Checkout;

use Minds\Traits\MagicAttributes;

/**
 * @method Order getName(): string
 * @method Order getAmount(): int
 * @method Order getQuantity(): int
 * @method Order getCurrency(): string
 * @method Order getServiceFeePct(): int
 * @method Order getCustomerId(): string
 * @method Order getStripeAccountId(): string
 */
class Order
{
    use MagicAttributes;

    /** @var string $name */
    private $name;

    /** @var int $amount */
    private $amount = 0;

    /** @var int $quantity */
    private $quantity = 1;

    /** @var string $currency */
    private $currency = 'usd';

    /** @var int $serviceFeePct */
    private $serviceFeePct = 0;

    /** @var string $customerId */
    private $customerId;

    /** @var string $stripeAccountId */
    private $stripeAccountId;

    /**
     * Return the service
     * @return int
     */
    public function getServiceFee(): int
    {
        return $this->amount * ($this->serviceFeePct / 100);
    }

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export(array $extend = []) : array
    {
        return [
            'name' => (string) $this->name,
            'amount' => $this->getAmount(),
            'quantity' => $this->getQuantity(),
            'currency' => $this->getCurrency(),
            'service_fee_pct' => $this->getServiceFeePct(),
            'service_fee' => $this->getServiceFee(),
            'stripe_account_id' => $this->getStripeAccountId(),
        ];
    }
}
