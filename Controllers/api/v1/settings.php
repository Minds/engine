<?php
/**
 * Minds Settings API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager as EmailConfirmation;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Audit\Services\AuditService;
use Minds\Core\Settings\Manager as SettingsManager;
use Minds\Entities;
use Minds\Entities\User;
use Minds\Exceptions\TwoFactorRequired;
use Minds\Exceptions\UserErrorException;
use Minds\Interfaces;
use Zend\Diactoros\ServerRequestFactory;

class settings implements Interfaces\Api
{
    protected Save $save;

    public function __construct()
    {
        $this->save = new Save();
    }

    /**
     * Extended channel
     *
     * @SWG\GET(
     *     summary="Return settings",
     *     path="/v1/settings",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        if (Core\Session::getLoggedInUser()->isAdmin() && isset($pages[0])) {
            $user = Di::_()->get(EntitiesBuilder::class)->single($pages[0]);
        } else {
            $user = Core\Session::getLoggedInUser();
        }

        if (!$user instanceof User) {
            return;
        }
    
        $response = [];

        $response['channel'] = $user->export();
        $response['channel']['email'] = $user->getEmail();
        $response['channel']['boost_rating'] = $user->getBoostRating();
        $response['channel']['disabled_emails'] = $user->disabled_emails;
        $response['channel']['toaster_notifications'] = $user->getToasterNotifications();

        $twoFactorManager = Di::_()->get('Security\TwoFactor\Manager');
        $response['channel']['has2fa'] = [
            'totp' => $twoFactorManager->isTwoFactorEnabled($user) && !$user->telno,
            'sms' => !!$user->telno,
        ];

        $sessionsManager = Di::_()->get('Sessions\Manager');
        $sessionsManager->setUser($user);
        $response['channel']['open_sessions'] = $sessionsManager->getActiveCount();

        // --------------------------
        /** @var SettingsManager $settingsV3Manager */
        $settingsV3Manager = Di::_()->get('Settings\Manager');

        $settingsV3 = $settingsV3Manager
            ->setUser($user)
            ->getUserSettings(true);

        $response['channel']['boost_partner_suitability'] = $settingsV3->getBoostPartnerSuitability();
        // --------------------------

        // Analytics opt out
        $response['channel']['opt_out_analytics'] = $user->isOptOutAnalytics();

        return Factory::response($response);
    }

    /**
     * Registers a user
     * @param array $pages
     *
     * @SWG\Post(
     *     summary="Update settings",
     *     path="/v1/settings",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function post($pages)
    {
        Factory::isLoggedIn();

        if (Core\Session::getLoggedInUser()->isAdmin() && isset($pages[0])) {
            $user = Di::_()->get(EntitiesBuilder::class)->single($pages[0]);
        } else {
            $user = Core\Session::getLoggedInUser();
        }

        if (!$user instanceof User) {
            return;
        }

        $twoFactorManager = Di::_()->get('Security\TwoFactor\Manager');

        try {
            if (isset($_POST['name']) && $_POST['name']) {
                $user->name = trim($_POST['name']);
            }

            $emailChange = false;

            if (isset($_POST['email']) && $_POST['email']) {
                if (Di::_()->get('Email\SpamFilter')->isSpam($_POST['email'])) {
                    return Factory::response(['status' => 'error', 'message' => "This email provider is blocked due to spam. Please use another address."]);
                }

                try {
                    if (!\validate_email_address($_POST['email'])) {
                        throw new \RegistrationException("Invalid email");
                    }
                } catch (\Exception) {
                    return Factory::response(['status' => 'error', 'message' => "Invalid email"]);
                }

                // If email is confirmed and account is older than 1 month and force two factor.
                if ($user->isEmailConfirmed() || $user->getAge() > 2629746) {
                    $twoFactorManager->gatekeeper($user, ServerRequestFactory::fromGlobals());
                }

                if (strtolower($_POST['email']) !== strtolower($user->getEmail())) {
                    $emailChange = true;
                }

                $user->setEmail(strtolower($_POST['email']));
            }

            if (isset($_POST['boost_partner_suitability'])) {
                /** @var SettingsManager $settingsV3Manager */
                $settingsV3Manager = Di::_()->get('Settings\Manager');

                $settingsV3Manager
                    ->setUser($user)
                    ->storeUserSettings(['boost_partner_suitability' => (int)$_POST['boost_partner_suitability']]);
            }

            if (isset($_POST['opt_out_analytics'])) {
                $user->setOptOutAnalytics($_POST['opt_out_analytics']);
            }

            if (isset($_POST['boost_rating'])) {
                $user->setBoostRating((int) $_POST['boost_rating']);
            }

            if (isset($_POST['boost_autorotate'])) {
                $user->setBoostAutorotate((bool) $_POST['boost_autorotate']);
            }

            if (isset($_POST['liquidity_spot_opt_out'])) {
                $user->setLiquiditySpotOptOut((int) $_POST['liquidity_spot_opt_out']);
            }

            if (isset($_POST['mature'])) {
                $user->setViewMature(isset($_POST['mature']) && (int) $_POST['mature']);
            }

            if (isset($_POST['monetized']) && $_POST['monetized']) {
                $user->monetized = $_POST['monetized'];
            }

            if (isset($_POST['disabled_emails'])) {
                $user->disabled_emails = (bool) $_POST['disabled_emails'];
            }

            if (isset($_POST['password']) && $_POST['password']) {
                try {
                    $password = new Core\Security\Password();
                    // Rate limit checked in here too
                    if (!$password->check($user, $_POST['password'])) {
                        return Factory::response([
                        'status' => 'error',
                        'message' => 'You current password is incorrect'
                    ]);
                    }
                } catch (Core\Security\Exceptions\PasswordRequiresHashUpgradeException $e) {
                }

                try {
                    validate_password($_POST['new_password']);
                } catch (\Exception $e) {
                    $response = ['status'=>'error', 'message'=>$e->getMessage()];

                    return Factory::response($response);
                }

                // Force two factor
                $twoFactorManager->gatekeeper($user, ServerRequestFactory::fromGlobals());

                //need to create a new salt and hash...
                //$user->salt = Core\Security\Password::salt();
                $user->password = Core\Security\Password::generate($user, $_POST['new_password']);
                $user->override_password = true;

                Di::_()->get('Sessions\CommonSessions\Manager')->deleteAll($user);

                Di::_()->get(AuditService::class)->log(
                    event: 'password_change',
                    properties: [],
                    user: $user,
                );
            }

            /** @var Core\I18n\Manager $i18n */
            $i18n = Di::_()->get('I18n\Manager');

            $language = $_POST['language'] ?? '';

            if ($language && $i18n->isLanguage($language)) {
                $user->setLanguage($language);
                $i18n->setLanguageCookie($language);
            }

            if (isset($_POST['toaster_notifications'])) {
                $user->setToasterNotifications((bool) $_POST['toaster_notifications']);
            }

            if (isset($_POST['disable_autoplay_videos'])) {
                $user->setDisableAutoplayVideos((bool) $_POST['disable_autoplay_videos']);
            }

            if (isset($_POST['hide_share_buttons'])) {
                $user->setHideShareButtons((bool) $_POST['hide_share_buttons']);
            }

            if (isset($_POST['allow_unsubscribed_contact'])) {
                $user->setAllowUnsubscribedContact((bool) $_POST['allow_unsubscribed_contact']);
            }

            $response = [];
            if (!$this->save->setEntity($user)->save()) {
                $response['status'] = 'error';
            }

            if ($emailChange) {
                /** @var EmailConfirmation $emailConfirmation */
                $emailConfirmation = Di::_()->get('Email\Confirmation');
                $emailConfirmation
                ->setUser($user);

                $reset = $emailConfirmation
                ->reset();

                if ($reset) {
                    $emailConfirmation->generateConfirmationToken();
                } else {
                    error_log('Cannot reset email confirmation for ' . $user->guid);
                }
            }
        } catch (TwoFactorRequired $e) {
            header('HTTP/1.1 ' . $e->getCode(), true, $e->getCode());
            $response['status'] = "error";
            $response['code'] = $e->getCode();
            $response['message'] = $e->getMessage();
            $response['errorId'] = str_replace('\\', '::', get_class($e));
            return Factory::response($response);
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
