<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Exceptions;

use Minds\Exceptions\UserErrorException;

class TooManySiteMembershipsException extends UserErrorException
{
    protected $message = 'Maximum allowed number of memberships reached.';
}
