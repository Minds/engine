<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Router\Exceptions;

class UnverifiedEmailException extends \Exception
{
    public function __construct()
    {
        $this->message = 'You must verify your account';
    }
}
