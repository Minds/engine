<?php

namespace Minds\Core\Blockchain\Skale\Escrow;

use Minds\Traits\MagicAttributes;
use Minds\Entities\User;

/**
 * EscrowTransaction object - containing sender, receiver and skale tx hash.
 * @method EscrowTransaction setSender(User $sender)
 * @method User getSender()
 * @method EscrowTransaction setReceiver(User $receiver)
 * @method User getReceiver()
 * @method EscrowTransaction setTxHash(string $txHash)
 * @method string getTxHash()
 */
class EscrowTransaction
{
    use MagicAttributes;

    /** @var User - sender of transaction */
    private $sender;

    /** @var User - receiver of transaction */
    private $receiver;

    /** @var string - skale transaction hash */
    private $txHash;
}
