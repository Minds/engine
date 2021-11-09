<?php
/**
 * Friendly exception for when an entity or anything else can't
 * be found
 */
namespace Minds\Exceptions;

class NotFoundException extends UserErrorException
{
    /** @var int */
    protected $code = 404;

    /** @var string */
    protected $message = "Could not find entity";
}
