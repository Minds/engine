<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\SMS\Exceptions;

class VoIpPhoneException extends \Exception
{
    public function __construct($message = null, \Throwable $previous = null)
    {
        $this->message = $message ?? 'voip phones are not allowed';
        parent::__construct($this->message, 0, $previous);
    }
}
