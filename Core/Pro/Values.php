<?php
/**
 * Values
 * @author edgebal
 */

namespace Minds\Core\Pro;

use JsonSerializable;
use Minds\Traits\MagicAttributes;

/**
 * Class Values
 * @package Minds\Core\Pro
 * @method int|string getUserGuid()
 * @method Values setUserGuid(int|string $userGuid)
 * @method string getDomain()
 * @method Values setDomain(string $domain)
 */
class Values implements JsonSerializable
{
    use MagicAttributes;

    /** @var int */
    protected $userGuid;

    /** @var string */
    protected $domain;

    /**
     * @return array
     */
    public function export()
    {
        return [
            'user_guid' => $this->userGuid,
            'domain' => $this->domain,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->export();
    }
}
