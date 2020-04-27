<?php
/**
 * EarningsBalance
 */
namespace Minds\Core\Monetization\Partners;

use Minds\Traits\MagicAttributes;

/**
 * @method EarningsBalance setUserGuid(string $guid)
 * @method string getUserGuid()
 * @method EarningsBalance setAmountCents(int $cents)
 * @method int getAmountCents()
 * @method EarningsBalance setAmountTokens(int $tokens)
 * @method int getAmountTokens()
 */
class EarningsBalance
{
    use MagicAttributes;

    /** @var string */
    private $userGuid;

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
            'amount_cents' => (int) $this->amountCents,
            'amount_usd' => (int) $this->amountCents / 100,
            'amount_tokens' => (string) $this->amountTokens,
        ];
    }
}
