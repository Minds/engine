<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Router\Exceptions;

use Minds\Interfaces\SentryExceptionExclusionInterface;

class UnverifiedEmailException extends \Exception implements SentryExceptionExclusionInterface
{
    public function __construct()
    {
        $this->message = 'You must verify your account';
    }
}
