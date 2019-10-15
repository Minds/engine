<?php
/**
 * EarningsDeposit
 */
namespace Minds\Core\Monetization\Partners;

use Minds\Traits\MagicAttributes;

class EarningsDeposit
{
    use MagicAttributes;

    /** @var string */
    private $userGuid;

    /** @var int */
    private $timestamp;

    /** @var string */
    private $item;

    /** @var int */
    private $amountCents;

    /** @var int */
    private $amountTokens;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'user_guid' => $this->userGuid,
            'timestamp' => $this->timestamp,
            'item' => $this->item,
            'amount_cents' => (int) $this->amountCents,
            'amount_usd' => (int) $this->amountCents / 100,
            'amount_tokens' => (string) $this->amountTokens,
        ];
    }
}
