<?php

namespace Minds\Exceptions;

use Exception;
use Minds\Interfaces\SentryExceptionExclusionInterface;

/**
 * Exception thrown when an item or action is being skipped
 */
class SkippingException extends Exception implements SentryExceptionExclusionInterface
{
}
