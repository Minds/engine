<?php

namespace Minds\Core\Blockchain\Skale\Escrow;

use Minds\Traits\MagicAttributes;
use Minds\Entities\User;

/**
 * Participants object - containing sender and receiver.
 * @method Participants setSender(User $sender)
 * @method User getSender()
 * @method Participants setReceiver(User $receiver)
 * @method User getReceiver()
 */
class Participants
{
    use MagicAttributes;

    /** @var User - sender of transaction */
    private $sender;

    /** @var User - receiver of transaction */
    private $receiver;
}
