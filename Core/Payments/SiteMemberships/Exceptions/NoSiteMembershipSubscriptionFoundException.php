<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Exceptions;

use Minds\Exceptions\UserErrorException;

class NoSiteMembershipSubscriptionFoundException extends UserErrorException
{
    protected $message = 'No site membership subscription found';
    protected $code = 404;
}
