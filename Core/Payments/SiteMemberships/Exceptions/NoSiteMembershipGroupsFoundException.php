<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Exceptions;

use Minds\Exceptions\NotFoundException;

class NoSiteMembershipGroupsFoundException extends NotFoundException
{
    protected $message = 'No site membership groups found.';
}
