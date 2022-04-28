<?php
namespace Minds\Exceptions;

use Minds\Interfaces\SentryExceptionExclusionInterface;

/**
 * Exception thrown when string length is determined to be invalid.
 */
class StringLengthException extends UserErrorException implements SentryExceptionExclusionInterface
{
}
