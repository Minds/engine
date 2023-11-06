<?php
namespace Minds\Core\MultiTenant\Exceptions;

use Minds\Exceptions\NotFoundException;

class NoTenantFoundException extends NotFoundException
{
    protected $message = "The tenant could not be found.";
}
