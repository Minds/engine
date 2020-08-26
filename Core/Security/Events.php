<?php

namespace Minds\Core\Security;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Security\TwoFactor;
use Minds\Exceptions;
use Minds\Helpers\Text;
use Minds\Core\Security\Spam;

class Events
{
    /** @var SMS $sms */
    protected $sms;

    /** @var Spam */
    protected $spam;

    public function __construct($spam = null)
    {
        $this->sms = Di::_()->get('SMS');
        $this->spam = $spam ?? new Spam();
    }

    public function register()
    {
        Dispatcher::register('create', 'elgg/event/object', [$this, 'onCreateHook']);
        Dispatcher::register('create', 'elgg/event/activity', [$this, 'onCreateHook']);
        Dispatcher::register('update', 'elgg/event/object', [$this, 'onCreateHook']);
    }

    public function onCreateHook($hook, $type, $params, $return = null)
    {
        $object = $params;

        if ($this->spam->check($object)) {
            if (PHP_SAPI != 'cli') {
                forward(REFERRER);
            }
            return false;
        }

        return true;
    }

    /**
     * Twofactor authentication login hook
     */
    public function onLogin($user)
    {
        global $TWOFACTOR_SUCCESS;

        if ($TWOFACTOR_SUCCESS == true) {
            return true;
        }

        if ($user->twofactor && !\elgg_is_logged_in()) {
            //send the user a twofactor auth code

            $twofactor = new TwoFactor();
            $secret = $twofactor->createSecret(); //we have a new secret for each request

            error_log('2fa - sending SMS to ' . $user->guid);

            $message = 'Minds verification code: '.$twofactor->getCode($secret);
            $this->sms->send($user->telno, $message);

            // create a lookup of a random key. The user can then use this key along side their twofactor code
            // to login. This temporary code should be removed within 2 minutes.
            $bytes = openssl_random_pseudo_bytes(128);
            $key = hash('sha512', $user->username . $user->salt . $bytes);

            $lookup = new \Minds\Core\Data\lookup('twofactor');
            $lookup->set($key, ['_guid' => $user->guid, 'ts' => time(), 'secret' => $secret]);

            //forward to the twofactor page
            throw new Exceptions\TwoFactorRequired($key);

            return false;
        }
    }
}
