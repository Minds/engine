<?php
/**
 * Settings.
 *
 * @author edgebal
 */

namespace Minds\Core\Pro;

use JsonSerializable;
use Minds\Traits\MagicAttributes;

/**
 * Class Settings.
 *
 * @method int|string getUserGuid()
 * @method Settings   setUserGuid(int|string $userGuid)
 * @method int        getTimeUpdated()
 * @method Settings   setTimeUpdated(int $timeUpdated)
 * @method string     getPayoutMethod()
 * @method Settings   setPayoutMethod(string $method)
 */
class Settings implements JsonSerializable
{
    use MagicAttributes;

    /** @var int */
    protected $userGuid;

    /** @var int */
    protected $timeUpdated;

    /** @var string */
    protected $payoutMethod = 'usd';

    /**
     * @return array
     */
    public function export(): array
    {
        return [
            'user_guid' => (string) $this->userGuid,
            'time_updated' => $this->timeUpdated,
            'payout_method' => $this->payoutMethod,
        ];
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
