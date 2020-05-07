<?php
/**
 * When no tags are set, throw this exception
 */
namespace Minds\Core\Discovery;

use Minds\Exceptions\UserErrorException;

class NoTagsException extends UserErrorException
{
    /** @var string */
    protected $message = "No tags have been set";
}
