<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Interfaces;

class register implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * NOT AVAILABLE
     */
    public function get($pages)
    {
        return Factory::response(['status' => 'error', 'message' => 'GET is not supported for this endpoint']);
    }

    /**
     * Registers a user
     * @param array $pages
     *
     * @SWG\Post(
     *     summary="Create a new channel",
     *     path="/v1/register",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function post($pages)
    {
        if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['username']) || !isset($_POST['email'])) {
            return Factory::response(['status' => 'error', 'message' => 'Please fill out all the fields']);
        }

        if (!$_POST['username'] || !$_POST['password'] || !$_POST['username'] || !$_POST['email']) {
            return Factory::response(['status' => 'error', 'message' => "Please fill out all the fields"]);
        }

        try {
            $captcha = Core\Di\Di::_()->get('Captcha\Manager');
            
            if (isset($_POST['captcha']) && !$captcha->verifyFromClientJson($_POST['captcha'])) {
                throw new \Exception('Captcha failed');
            }

            $ipHashVerify = Core\Di\Di::_()->get('Security\SpamBlocks\IPHash');
            if (!$ipHashVerify->isValid($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Sorry, you are not allowed to register.',
                ]);
            }

            $emailVerify = Core\Di\Di::_()->get('Email\Verify\Manager');
            if (!$emailVerify->verify($_POST['email'])) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Please verify your email address is correct',
                ]);
            }

            if (!(isset($_POST['parentId']) || isset($_POST['previousUrl']) || isset($_SERVER['HTTP_APP_VERSION']))) {
                return Factory::response(['status'=>'error', 'message' => "Please refresh your browser or update you app. We don't recognise your platform."]);
            }

            $user = register_user($_POST['username'], $_POST['password'], $_POST['username'], $_POST['email'], false);
            $guid = $user->guid;

            // Hacky, move to service soon!
            $hasSignupTags = false;
            if (isset($_COOKIE['mexp'])) {
                $manager = Core\Di\Di::_()->get('Experiments\Manager');
                $bucket = $manager->getBucketForExperiment('Homepage121119');
                $user->expHomepage200619 = $bucket->getId();
            }

            if (isset($_POST['parentId'])) {
                $user->signupParentId = (string) $_POST['parentId'];
                $hasSignupTags = true;
            }
            if (isset($_POST['previousUrl'])) {
                $user->signupPreviousUrl = (string) $_POST['previousUrl'];
                $hasSignupTags = true;
            }
            if (isset($_SERVER['HTTP_APP_VERSION'])) {
                $user->signupParentId = 'mobile-native';
                $hasSignupTags = true;
            }

            /** @var Core\I18n\Manager $i18n */
            $i18n = Di::_()->get('I18n\Manager');
            $language = $i18n->getLanguage();

            if ($language !== 'en') {
                $user->setLanguage($language);
                $hasSignupTags = true;
            }

            if ($hasSignupTags) {
                $user->save();
            } else {
                return Factory::response(['status'=>'error', 'message' => "Please refresh your browser or update you app. We don't recognise your platform."]);
            }

            $params = [
                'user' => $user,
                'password' => $_POST['password'],
                'friend_guid' => "",
                'invitecode' => "",
                'referrer' => isset($_COOKIE['referrer']) ? $_COOKIE['referrer'] : '',
            ];

            // TODO: Move full reguster flow to the core
            elgg_trigger_plugin_hook('register', 'user', $params, true);
            Core\Events\Dispatcher::trigger('register', 'user', $params);
            Core\Events\Dispatcher::trigger('register/complete', 'user', $params);

            $sessions = Di::_()->get('Sessions\Manager');
            $sessions->setUser($user);
            $sessions->createSession();
            $sessions->save(); // Save to db and cookie

            $response = [
                'guid' => $guid,
                'user' => $user->export(),
            ];
        } catch (\Exception $e) {
            error_log(
                "RegistrationError | username: ".$_POST['username']
                .", email:".$_POST['email']
                .", signupParentId".$user->signupParentId
                .", exception: ".$e->getMessage()
            );
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }
        return Factory::response($response);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
