<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Exceptions;

use Minds\Exceptions\UserErrorException;

class EntityTypeNotAllowedInLocationException extends UserErrorException
{
    protected $code = 400;
    protected $message = "Activity posts boosts can only appear in Newsfeed. Channel boosts can only appear in sidebar";
}
