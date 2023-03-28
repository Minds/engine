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

    /** @var float */
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
            'amount_cents' => (float) $this->amountCents,
            'amount_usd' => round(((float) $this->amountCents) / 100, 2),
            'amount_tokens' => (string) $this->amountTokens,
        ];
    }
}
