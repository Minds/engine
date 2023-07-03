<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Exceptions;

use Minds\Exceptions\ServerErrorException;

class BoostCreationFailedException extends ServerErrorException
{
    protected $code = 500;
    protected $message = 'An error was encountered while creating the boost.';
}
