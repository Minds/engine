<?php
/**
 * Minds Security Provider
 */

namespace Minds\Core\Security;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;

class SecurityProvider extends Provider
{
    public function register()
    {
        $this->di->bind(ACL::class, function (Di $di): ACL {
            return ACL::_();
        }, ['useFactory'=>true]);

        $this->di->bind('Security\ACL', function ($di) {
            return $di->get(ACL::class);
        }, ['useFactory'=>true]);

        $this->di->bind('Security\ACL\Block', function ($di) {
            return new ACL\Block(
                Di::_()->get('Security\Block\Manager')
            );
        }, ['useFactory'=>true]);

        $this->di->bind('Security\Captcha', function ($di) {
            return new Captcha(Di::_()->get('Config'));
        }, ['useFactory'=>true]);

        $this->di->bind('Security\ReCaptcha', function ($di) {
            return new ReCaptcha(Di::_()->get('Config'));
        }, ['useFactory'=>true]);

        $this->di->bind('Security\TwoFactor', function ($di) {
            return new TwoFactor();
        }, ['useFactory'=>false]);

        $this->di->bind('Security\LoginAttempts', function ($di) {
            return new LoginAttempts();
        }, ['useFactory' => false]);

        $this->di->bind('Security\Password', function ($di) {
            return new Password();
        }, ['useFactory' => false]);

        $this->di->bind('Security\Spam', function ($di) {
            return new Spam();
        }, ['useFactory' => true]);

        $this->di->bind('Security\Events', function ($di) {
            return new Events();
        }, ['useFactory' => true]);

        $this->di->bind('Security\SpamBlocks\IPHash', function ($di) {
            return new SpamBlocks\IPHash;
        }, ['useFactory' => true]);

        $this->di->bind('Security\RateLimits\InteractionsLimiter', function ($di) {
            return new RateLimits\InteractionsLimiter();
        }, ['useFactory' => false]);

        $this->di->bind('Security\RateLimits\KeyValueLimiter', function ($di) {
            return new RateLimits\KeyValueLimiter();
        }, ['useFactory' => false]);

        $this->di->bind('Security\DeferredSecrets', function ($di) {
            return new DeferredSecrets();
        }, ['useFactory' => false]);
    }
}
