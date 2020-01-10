<?php
/**
 * Stripe Connect Balance
 */
namespace Minds\Core\Payments\Stripe\Connect;

use Minds\Traits\MagicAttributes;

/**
 * @method Balance getAmount(): int
 * @method Balance getCurrency(): string
 */
class Balance
{
    use MagicAttributes;

    /** @var int $amount */
    private $amount;

    /** @var string $currency */
    private $currency;

    /**
     * Expose to public API
     * @return array
     */
    public function export(array $extend = []) : array
    {
        return [
            'amount' => (int) $this->amount,
            'currency' => $this->currency,
        ];
    }
}
