<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Traits\MagicAttributes;

/**
 * @method EarningsItemModel setId(string $id)
 * @method getId(): string
 * @method EarningsItemModel setAmountCents(int $cents)
 * @method int getAmountCents()
 * @method EarningsItemModel setCurrency(string $currency)
 * @method string getCurrency
 */
class EarningsItemModel
{
    use MagicAttributes;

    /** @var string */
    private $id;

    /** @var int */
    private $amountCents = 0;

    /** @var string */
    private $currency;
    
    /**
     * Export the overview
     * @return array
     */
    public function export(): array
    {
        return [
            'id' => $this->id,
            'amount_cents' => $this->amountCents,
            'amount_usd' => $this->amountCents / 100,
            'currency' => $this->currency,
        ];
    }
}
