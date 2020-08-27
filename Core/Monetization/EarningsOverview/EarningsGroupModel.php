<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Traits\MagicAttributes;

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

    public function getSum(): int
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
     * Export the overview
     * @return array
     */
    public function export(): array
    {
        $amountCents = $this->getSum();
        return [
            'id' => $this->id,
            'amount_cents' => $amountCents,
            'amount_usd' => $amountCents / 100,
            'currency' => $this->currency,
            'items' => array_map(function ($item) {
                return $item->export();
            }, $this->items),
        ];
    }
}
