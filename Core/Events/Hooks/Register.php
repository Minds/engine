<?php

namespace Minds\Core\Events\Hooks;

use Minds\Core;
use Minds\Core\Referrals\Referral;
use Minds\Entities;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Di\Di;

class Register
{
    public function init()
    {
        Dispatcher::register('register', 'user', function ($event) {
            $params = $event->getParameters();

            //$guid = $params['user']->guid;
            //subscribe to minds channel
            //$minds = new Entities\User('minds');
            //$params['user']->subscribe($minds->guid);

            //setup chat keys
            /*$openssl = new Core\Messenger\Encryption\OpenSSL();
            $keystore = (new Core\Messenger\Keystore($openssl))
                ->setUser($params['user']);
            $keypair = $openssl->generateKeypair($params['password']);

            $keystore->setPublicKey($keypair['public'])
                ->setPrivateKey($keypair['private'])
                ->save();*/

            //@todo again, maybe in a background task?
            if ($params['referrer']) {
                $user = new Entities\User(strtolower(ltrim($params['referrer'], '@')));
                if ($user->guid) {
                    $params['user']->referrer = (string) $user->guid;
                    $params['user']->save();
                    $params['user']->subscribe($user->guid);
                }
     
                $referral = new Referral();
                $referral->setProspectGuid($params['user']->getGuid())
                    ->setReferrerGuid((string) $user->guid)
                    ->setRegisterTimestamp(time());

                $manager = Di::_()->get('Referrals\Manager');
                $manager->add($referral);
            }
        });

        Dispatcher::register('register/complete', 'user', function ($event) {
            $params = $event->getParameters();
            //temp: if captcha failed
            if ($params['user']->captcha_failed) {
                return false;
            }

            try {
                /** @var Core\Email\Confirmation\Manager $emailConfirmation */
                $emailConfirmation = Di::_()->get('Email\Confirmation');
                $emailConfirmation
                    ->setUser($params['user'])
                    ->sendEmail();
            } catch (\Exception $e) {
                error_log((string) $e);
            }

            try {
                Core\Queue\Client::build()->setQueue('Registered')
                    ->send([
                        'user_guid' => (string) $params['user']->guid,
                    ]);
            } catch (\Exception $e) {
            }
        });
    }
}
