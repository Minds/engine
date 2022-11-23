<?php

declare(strict_types=1);

namespace Minds\Core\Payments\Models;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * Model representing options for GET requests to retrieve payments from Stripe.
 * @method string getCustomerId()
 * @method self setCustomerId(string $id)
 * @method string getStartingAfter()
 * @method self setStartingAfter(string $startingAfter)
 * @method int getLimit()
 * @method self setLimit(int $limit)
 */
class GetPaymentsOpts implements ExportableInterface
{
    use MagicAttributes;

    /** @var int requested items to return */
    private int $limit = 12;

    /** @var string|null payment id that acts as paging token */
    private ?string $startingAfter = null;

    /** @var string|null customer id to get payments for */
    private ?string $customerId = null;

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
        if ($this->getStartingAfter()) {
            $export['starting_after'] = $this->getStartingAfter();
        }
        if ($this->getLimit()) {
            $export['limit'] = $this->getLimit();
        }
        return $export;
    }
}
