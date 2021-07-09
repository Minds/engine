<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Traits\MagicAttributes;
use Brick\Math\BigDecimal;

/**
 * @method EarningsGroupModel setId(string $id)
 * @method getId(): string
 * @method EarningsGroupModel setItems(array $items)
 * @method array getItems()
 * @method EarningsGroupModel setCurrency(string $currency)
 * @method string getCurrency
 */
class EarningsGroupModel
{
    use MagicAttributes;

    /** @var string */
    private $id;

    /** @var array */
    private $items = [];

    /** @var string */
    private $currency = 'usd';

    /**
     * @return int
     */
    public function getSumCents(): int
    {
        return array_reduce(
            array_map(function ($item) {
                return $item->getAmountCents();
            }, $this->items),
            function ($carry, $item) {
                $carry += (int) $item;
                return $carry;
            },
            0
        );
    }

    /**
     * @return string
     */
    public function getSumTokens(): string
    {
        return BigDecimal::sum(...array_map(function ($item) {
            return $item->getAmountTokens();
        }, $this->items));
    }
    
    /**
     * Export the overview
     * @return array
     */
    public function export(): array
    {
        $amountCents = $this->getSumCents();
        $amountTokens = $this->getSumTokens();
        return [
            'id' => $this->id,
            'amount_cents' => $amountCents,
            'amount_usd' => $amountCents / 100,
            'amount_tokens' => $amountTokens,
            'currency' => $this->currency,
            'items' => array_map(function ($item) {
                return $item->export();
            }, $this->items),
        ];
    }
}
