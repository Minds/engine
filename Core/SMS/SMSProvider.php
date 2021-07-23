<?php
/**
 * Minds SMS Provider
 */

namespace Minds\Core\SMS;

use Minds\Core\Di\Provider;
use Minds\Core\SMS\Services\Twilio;
use Minds\Core\SMS\Services\TwilioVerify;

class SMSProvider extends Provider
{
    public function register()
    {
        $this->di->bind('SMS', function ($di) {
            return new Twilio();
        }, ['useFactory' => true]);
        $this->di->bind('SMS\Twilio\Verify', function ($di) {
            return new TwilioVerify();
        }, ['useFactory' => true]);
        $this->di->bind('SMS\SNS', function ($di) {
            return new Services\SNS();
        }, ['useFactory' => true]);
    }
}
