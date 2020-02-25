<?php
namespace Minds\Core\SMS;

use Minds\Core\SMS\Exceptions\InvalidPhoneException;

interface SMSServiceInterface
{
    /**
     * Verifies the number isn't from a voip line
     * @param $number
     * @return boolean
     * @throws InvalidPhoneException
     */
    public function verify($number);

    /**
     * Send an SMS
     * @param $number
     * @param $message
     * @return string - id
     */
    public function send($number, $message);
}
