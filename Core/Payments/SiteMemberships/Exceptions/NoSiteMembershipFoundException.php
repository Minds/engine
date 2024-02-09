<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Exceptions;

use Minds\Exceptions\NotFoundException;

class NoSiteMembershipFoundException extends NotFoundException
{
    protected $message = 'No site membership found.';
}
