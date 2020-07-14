<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Traits\MagicAttributes;

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
