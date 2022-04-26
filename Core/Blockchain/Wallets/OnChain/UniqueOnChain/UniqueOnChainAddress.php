<?php
namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Traits\MagicAttributes;

/**
 * @method string getAddress()
 * @method self setAddress(string $address)
 * @method string getUserGuid()
 * @method self setUserGuid(string $userGuid)
 * @method string getSignature()
 * @method self setSignature(string $signature)
 * @method string getPayload()
 * @method self setPayload(string $payload)
 * @method string getTokenBalance()
 * @method self setTokenBalance(string $balance)
 */
class UniqueOnChainAddress
{
    use MagicAttributes;

    /** @var string */
    protected $address;

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $signature;

    /** @var string */
    protected $payload;

    /** @var string */
    protected $tokenBalance;

    /**
     * Public export for address
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'address' => $this->address,
            'user_guid' => $this->userGuid,
        ];
    }
}
