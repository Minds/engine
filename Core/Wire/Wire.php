<?php
/**
 * Wire model
 */

namespace Minds\Core\Wire;

use Minds\Core\Guid;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Class Wire
 * @package Minds\Core\Wire
 * @method Wire setGuid(int $value)
 * @method User getReceiver()
 * @method Wire setReceiver(User $value)
 * @method User getEntity()
 * @method Wire setEntity(User $value)
 * @method User getSender()
 * @method Wire setSender(User $value)
 * @method string getAmount()
 * @method Wire setAmount(string $value)
 * @method bool getRecurring()
 * @method bool isRecurring()
 * @method Wire setRecurring(bool $value)
 * @method string getRecurringInterval()
 * @method Wire setRecurringInterval(string $value)
 * @method string getMethod()
 * @method Wire setMethod(string $value)
 * @method string getAddress()
 * @method Wire setAddress(string $value)
 * @method int getTimestamp()
 * @method Wire setTimestamp(int $value)
 * @method int getTrialDays()
 * @method Wire setTrialDays(int $value)
 */
class Wire
{
    use MagicAttributes;

    /** @var int */
    private $guid;

    /** @var User */
    private $receiver;

    /** @var User */
    private $entity;

    /** @var User */
    private $sender;

    /** @var string */
    private $amount;

    /** @var bool */
    private $recurring = false;

    /** @var string */
    private $recurringInterval;

    /** @var string $method */
    private $method = 'tokens';

    /** @var string $address */
    private $address;

    /** @var int $timestamp */
    private $timestamp;

    /** @var int $trialDays */
    private $trialDays;

    public function getGuid()
    {
        if (!$this->guid) {
            $this->guid = Guid::build();
        }

        return $this->guid;
    }

    public function export()
    {
        return [
            'timestamp' => $this->timestamp,
            'amount' => $this->amount,
            'receiver' => $this->receiver->export(),
            'sender' => $this->sender->export(),
            'recurring' => $this->recurring,
        ];
    }
}
