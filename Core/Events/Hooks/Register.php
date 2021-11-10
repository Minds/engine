<?php

namespace Minds\Core\Events\Hooks;

use Minds\Core;
use Minds\Core\Referrals\Referral;
use Minds\Entities;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Cookie\Manager as ExperimentsCookie;

class Register
{
    /** @var Logger */
    protected $logger;

    /** @var ExperimentsCookie */
    protected $experimentsCookie;

    public function __construct($logger = null, $experimentsCookie = null)
    {
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->experimentsCookie = $experimentsCookie ?? Di::_()->get('Experiments\Cookie\Manager');
    }

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
                try {
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
                } catch (\Exception $e) {
                    if ($e->getCode() !== 404) {
                        $this->logger->error($e);
                    }
                }
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
                /** @var Entities\User $user */
                $user = $params['user'];

                $platform = 'browser';
                if ($user->signupParentId === 'mobile-native') {
                    $platform = 'mobile';
                }

                $event = new Core\Analytics\Metrics\Event();
                $event
                    ->setType('action')
                    ->setAction('signup')
                    ->setProduct('platform')
                    ->setPlatform($platform)
                    ->setUserGuid($user->guid)
                    ->setCookieId($_COOKIE['mwa'] ?? '')
                    ->setLoggedIn(true);

                if ($user->referrer) {
                    $event->setReferrerGuid($user->referrer);

                    try {
                        $referrer = new Entities\User($user->referrer, false);

                        if ($referrer && $referrer->guid) {
                            $event->setProReferrer($referrer->isPro());
                        }
                    } catch (\Exception $e) {
                        // Do not fail if we couldn't find referrer user
                        // Might be deleted, disabled or banned
                    }
                }

                $event->push();

                // delete experiments cookie as it will contain a logged-out placeholder guid.
                $this->experimentsCookie->delete();
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
