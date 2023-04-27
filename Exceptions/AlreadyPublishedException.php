<?php
declare(strict_types=1);

namespace Minds\Exceptions;

/**
 * Exception to be thrown when an action cannot be performed because
 * an entity is already published.
 */
class AlreadyPublishedException extends UserErrorException
{
    /** @var int */
    protected $code = 400;

    /** @var string */
    protected $message = "This entity is already published";
}
