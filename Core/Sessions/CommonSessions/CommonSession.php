<?php
/**
 * Encompasses both JWT and OAuth sessions
 */

namespace Minds\Core\Sessions\CommonSessions;

use Minds\Traits\MagicAttributes;

/**
 * Class CommonSession
 * @package Minds\Core\Sessions\CommonSessions
 * @method string getId()
 * @method Session setId(string $id)
 * @method int|string getUserGuid()
 * @method Session setUserGuid(int|string $userGuid)
 * @method int getExpires()
 * @method Session setExpires(int $expires)
 * @method string getIp()
 * @method Session setIp(string $ip)
 * @method string getPlatform()
 * @method Session setPlatform(string $platform)
 * @method int getLastActive()
 * @method Session setLastActive(int $expires)
 */
class CommonSession
{
    use MagicAttributes;

    /** @var string */
    private $id;

    /** @var int */
    private $userGuid;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    /** @var int */
    private $lastActive;

    /**
     * Public export for common session
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'id' => $this->id,
            'user_guid' => $this->userGuid,
            'ip' => $this->ip,
            'platform' => $this->platform,
            'last_active' => $this->lastActive,
        ];
    }
}
