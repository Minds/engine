<?php

declare(strict_types=1);

namespace Minds\Core\Settings\Exceptions;

use Minds\Exceptions\NotFoundException;

class UserSettingsNotFoundException extends NotFoundException
{
    protected $code = 404;
    protected $message = "No settings found";
}
