<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Exceptions;

/**
 * Thrown when access to a boost is forbidden - for example because of a failed ACL read check.
 */
class BoostAccessForbiddenException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 403;
    protected $message = 'You do not have permission to view this Boost';
}
