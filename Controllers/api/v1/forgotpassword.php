<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Security\RateLimits\RateLimitExceededException;

class forgotpassword implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /** @var ActionButton */
    protected $actionButton;

    /**
     * NOT AVAILABLE
     */
    public function get($pages)
    {
        return Factory::response(['status'=>'error', 'message'=>'GET is not supported for this endpoint']);
    }

    /**
     * Resets a forgotten password
     * @param array $pages
     *
     * @SWG\Post(
     *     summary="Reset a password",
     *     path="/v1/forgotpassword",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function post($pages)
    {
        $response = [];

        if (!isset($pages[0])) {
            $pages[0] = "request";
        }

        switch ($pages[0]) {
        case "request":

          try {
              $rateLimitCheck = Di::_()->get("Security\RateLimits\KeyValueLimiter")
                  ->setKey('forgot-password-ips')
                  ->setValue($_SERVER['HTTP_X_FORWARDED_FOR'])
                  ->setSeconds(86400) // Day
                  ->setMax(5)
                  ->checkAndIncrement();
          } catch (RateLimitExceededException $e) {
              $response['status'] = "error";
              $response['message'] = $e->getMessage();
              break;
          }

          $user = new Entities\User(strtolower($_POST['username']));
          if (!$user->guid) {
              $response['status'] = "error";
              $response['message'] = "Could not find @" . $_POST['username'];
              break;
          }
          $code = Core\Security\Password::reset($user);
          $link = elgg_get_site_url() . "forgot-password;username=" . $user->username . ";code=" . $code;

          //now send an email
          $mailer = Di::_()->get('Mailer');
          $message = new Core\Email\V2\Common\Message();
          $template = new Core\Email\V2\Common\Template();
          $translator = $template->getTranslator();

          $subject = $translator->trans('Password reset');

          //prepare the action button
          $actionButton = (new ActionButton())
           ->setPath($link)
           ->setLabel($translator->trans('Reset Password'));

          $template
            ->setLocale($user->getLanguage())
            ->setTemplate('default.tpl')
            ->setBody(dirname(dirname(dirname(dirname(__FILE__)))) . '/Core/Email/V2/Campaigns/Recurring/ForgotPassword/template.tpl')
            ->set('user', $user)
            ->set('username', $user->username)
            ->set('link', $link)
            ->set('signoff', $translator->trans('Thank you,'))
            ->set('preheader', $translator->trans('Reset your password by clicking this link.'))
            ->set('title', $subject)
            ->set('actionButton', $actionButton->build());
          $message->setTo($user)
            ->setSubject($subject)
            ->setHtml($template);
          $mailer->queue($message, true);

          error_log(
              "ForgotPasswordRequest "
              .", guid: {$user->guid}"
              .", addr: " . $_SERVER['HTTP_X_FORWARDED_FOR']
          );
 
          break;
        case "reset":
          $user = new Entities\User(strtolower($_POST['username']));
          if (!$user->guid) {
              $response['status'] = "error";
              $response['message'] = "Could not find @" . $_POST['username'];
              break;
          }

          if (!$user->password_reset_code) {
              $response['status'] = "error";
              $response['message'] = "Please try again with a new reset code.";
              break;
          }

          if ($user->password_reset_code && $user->password_reset_code !== $_POST['code']) {
              $response['status'] = "error";
              $response['message'] = "The reset code is invalid";
              break;
          }

          if (!isset($_POST['code']) || !$_POST['code'] || !is_string($_POST['code']) || !strlen($_POST['code']) > 10) {
              $response['status'] = "error";
              $response['message'] = "The reset code is invalid";
              break;
          }

          try {
              if (!validate_password($_POST['password'])) {
                  $response['status'] = "error";
                  $response['message'] = "Password must have more than 8 characters. Including uppercase, numbers, special characters (ie. !,#,@), and cannot have spaces.";
              }
          } catch (\Exception $e) {
              $response['status'] = "error";
              $response['message'] = "Password must have more than 8 characters. Including uppercase, numbers, special characters (ie. !,#,@), and cannot have spaces.";
              break;
          }

          //$user->salt = Core\Security\Password::salt();
          $user->password = Core\Security\Password::generate($user, $_POST['password']);
          $user->password_reset_code = "";
          $user->override_password = true;
          $user->save();

          (new \Minds\Core\Data\Sessions())->destroyAll($user->guid);

          $sessions = Core\Di\Di::_()->get('Sessions\Manager');
          $sessions->setUser($user);
          $sessions->createSession();
          $sessions->save(); // save to db and cookie

          $response['user'] = $user->export();

          break;
        default:
          $response = ['status'=>'error', 'message'=>'Unknown endpoint'];
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
