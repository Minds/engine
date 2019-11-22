<?php
/**
 * Minds Session
 */

namespace Minds\Core\Sessions;

use Lcobucci\JWT\Token as JwtToken;
use Minds\Traits\MagicAttributes;

/**
 * Class Session
 * @package Minds\Core\Sessions
 * @method string getId()
 * @method Session setId(string $id)
 * @method string|JwtToken getToken()
 * @method Session setToken(string|JwtToken $token)
 * @method int|string getUserGuid()
 * @method Session setUserGuid(int|string $userGuid)
 * @method int getExpires()
 * @method Session setExpires(int $expires)
 */
class Session
{
    use MagicAttributes;

    /** @var string $id */
    private $id;

    /** @var string|JwtToken $token */
    private $token;

    /** @var int $userGuid */
    private $userGuid;

    /** @var int $expires */
    private $expires;
}
