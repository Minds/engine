<?php
namespace Minds\Core\Rewards\Withdraw;

use JsonSerializable;
use Minds\Common\Access;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Class Request
 * @package Minds\Core\Rewards\Withdraw
 * @method string getTx()
 * @method Request setTx(string $tx)
 * @method string getCompletedTx()
 * @method Request setCompletedTx(string $completedTx)
 * @method string getAddress()
 * @method Request setAddress(string $address)
 * @method int|string getUserGuid()
 * @method Request setUserGuid(int|string $userGuid)
 * @method double getGas()
 * @method Request setGas(double $gas)
 * @method string getAmount()
 * @method Request setAmount(string $amount)
 * @method string getStatus()
 * @method Request setStatus(string $status)
 * @method bool isCompleted()
 * @method Request setCompleted(bool $completed)
 * @method int getTimestamp()
 * @method Request setTimestamp(int $timestamp)
 * @method User|null getUser()
 * @method Request setUser(User|null $user)
 * @method User|null getReferrer()
 * @method Request setReferrer(User|null $referrer)
 */
class Request implements JsonSerializable, EntityInterface
{
    use MagicAttributes;

    /** @var string **/
    protected $tx;

    /** @var string **/
    protected $completedTx;

    /** @var string **/
    protected $address;

    /** @var int|string **/
    protected $userGuid;

    /** @var double **/
    protected $gas;

    /** @var string **/
    protected $amount;

    /** @var string */
    protected $status;

    /** @var bool **/
    protected $completed;

    /** @var int **/
    protected $timestamp;

    /** @var User */
    protected $user;

    /** @var User */
    protected $referrer;

    /**
     * @return array
     */
    public function export(): array
    {
        $data = [
            'timestamp' => $this->timestamp,
            'amount' => $this->amount,
            'user_guid' => $this->userGuid,
            'tx' => $this->tx,
            'status' => $this->status,
            'completed' => $this->completed,
            'completed_tx' => $this->completedTx,
        ];

        if ($this->user) {
            $data['user'] = $this->user->export();
        }

        if ($this->referrer) {
            $data['referrer'] = $this->referrer->export();
        }

        return $data;
    }

    /**
     * User guid owner for entity purposes
     * @return string
     */
    public function getOwnerGuid(): string
    {
        return $this->userGuid;
    }

    /**
     * No concept of GUID for withdrawals
     * @return string|null
     */
    public function getGuid(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUrn(): string
    {
        return 'urn:withdraw-request:' . implode('-', [ $this->userGuid, $this->timestamp, $this->tx]);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'withdraw';
    }

    /**
     * @return string
     */
    public function getSubtype(): string
    {
        return 'request';
    }

    public function getAccessId(): string
    {
        return Access::idToString(Access::UNLISTED);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
