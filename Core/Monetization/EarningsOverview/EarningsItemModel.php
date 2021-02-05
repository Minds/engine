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
 * @method EarningsItemModel setAmoountTokens(string $tokens)
 * @method string getAmountTokens()
 */
class EarningsItemModel
{
    use MagicAttributes;

    /** @var string */
    private $id;

    /** @var int */
    private $amountCents = 0;

    /** @var string */
    private $amountTokens = "0";

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
            'amount_tokens' => $this->amountTokens,
            'currency' => $this->currency,
        ];
    }
}
