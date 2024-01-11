<?php

namespace Minds\Core\Events\Hooks;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Referrals\Referral;
use Minds\Entities;

class Register
{
    /** @var Logger */
    protected $logger;

    public function __construct($logger = null)
    {
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    public function init()
    {
        Dispatcher::register('register', 'user', function ($event) {
            $params = $event->getParameters();

            //@todo again, maybe in a background task?

            $referrer = $params['referrer'];
            $user = $params['user'];

            if ($referrer) {
                try {
                    $user = new Entities\User(strtolower(ltrim($referrer, '@')));
                    if ($user->guid) {
                        $user->referrer = (string)$user->guid;

                        (new Save())->setEntity($user)->withMutatedAttributes(['referrer'])->save();

                        $user->subscribe($user->guid);
                    }

                    $referral = new Referral();
                    $referral->setProspectGuid($user->getGuid())
                        ->setReferrerGuid((string)$user->guid)
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
                    ->generateConfirmationToken();
            } catch (\Exception $e) {
                error_log((string)$e);
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
            } catch (\Exception $e) {
                error_log((string)$e);
            }

            try {
                Core\Queue\Client::build()->setQueue('Registered')
                    ->send([
                        'user_guid' => (string)$params['user']->guid,
                        'invite_token' => $params['invitecode'] ?? null,
                    ]);
            } catch (\Exception $e) {
            }
        });
    }
}
