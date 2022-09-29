<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings\Exceptions;

use Minds\Exceptions\NotFoundException;

/**
 * Settings not found exception.
 */
class SettingsNotFoundException extends NotFoundException
{
    /** @var string */
    protected $message = "No user settings are set";
}
