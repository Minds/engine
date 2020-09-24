<?php
/**
 * EarningsPayout
 */
namespace Minds\Core\Monetization\Partners;

use Minds\Traits\MagicAttributes;

class EarningsPayout
{
    use MagicAttributes;

    /** @var string */
    private $userGuid;

    /** @var User */
    private $user;

    /** @var int */
    private $timestamp;

    /** @var string */
    private $method;

    /** @var int */
    private $amountCents;

    /** @var int */
    private $amountTokens;

    /** @var string */
    private $destinationId;

    /** @var bool */
    private $suspect;

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
            'amount_cents' => (int) $this->amountCents,
            'amount_usd' => (int) $this->amountCents / 100,
            'amount_tokens' => (string) $this->amountTokens,
        ];
    }
}
