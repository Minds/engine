<?php

declare(strict_types=1);

namespace Minds\Core\Payments\Models;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * Model representing options for GET requests to retrieve payments from Stripe.
 * @method string getCustomerId()
 * @method self setCustomerId(string $id)
 * @method string getEndingBefore()
 * @method self setEndingBefore(string $endingBefore)
 * @method int getLimit()
 * @method self setLimit(int $limit)
 */
class GetPaymentsOpts implements ExportableInterface
{
    use MagicAttributes;

    /** @var int requested items to return */
    private int $limit = 12;

    /** @var string|null payment id to get payments BEFORE */
    private ?string $endingBefore = null;

    /** @var string customer id to get payments for */
    private string $customerId;

    /**
     * Export options as array to be passed to Stripe API.
     * @param array $extras - extras to add to array.
     * @return array exported options to be passed to API.
     */
    public function export(array $extras = []): array
    {
        $export = [];
        if ($this->getCustomerId()) {
            $export['customer'] = $this->getCustomerId();
        }
        if ($this->getEndingBefore()) {
            $export['ending_before'] = $this->getEndingBefore();
        }
        if ($this->getLimit()) {
            $export['limit'] = $this->getLimit();
        }
        return $export;
    }
}
