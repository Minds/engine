<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Exceptions;

use Minds\Exceptions\NotFoundException;

class NoSiteMembershipRolesFoundException extends NotFoundException
{
    protected $message = 'No site membership roles found.';
}
