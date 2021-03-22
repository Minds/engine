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
}
