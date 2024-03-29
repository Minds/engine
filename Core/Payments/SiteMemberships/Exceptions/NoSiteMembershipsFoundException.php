<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Exceptions;

use Minds\Exceptions\NotFoundException;

class NoSiteMembershipsFoundException extends NotFoundException
{
    protected $message = 'No site memberships found.';
}
