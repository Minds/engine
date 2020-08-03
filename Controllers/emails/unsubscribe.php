<?php
/**
 * Minds email unsubscribe folder controller.
 */
namespace Minds\Controllers\emails;

use Minds\Core;
use Minds\Entities\User;
use Minds\Interfaces;

class unsubscribe extends core\page implements Interfaces\page
{
    /**
     * Get requests
     */
    public function get($pages)
    {
        \elgg_set_ignore_access();

        $siteUrl = Core\Config::_()->site_url;

        try {
            $campaign = strtolower($pages[2]);
            $topic = strtolower($pages[3]);
            $email = strtolower(urldecode($pages[1]));
            $userGuid = strtolower($pages[0]);
            $user = new User($userGuid);

            if ($user->getEmail() == $email) {
                /** @var Core\Email\Manager $manager */
                $manager = Core\Di\Di::_()->get('Email\Manager');

                $manager->unsubscribe($user, [ $campaign ], [ $topic ]);
                $user->save();
            } else {
                throw new \Exception('UnsubscribeSaveException');
            }

            echo <<<HTML
                <img src="https://d15u56mvtglc6v.cloudfront.net/front/public/assets/logos/medium-production.png" alt="Minds.com" align="middle" width="200px" height="80px"/>
                <h1 style="color:rgb(119, 119, 119);">SUCCESS. You won't receive any more emails like this for @$user->username</h1>
                <strong style="color:rgb(119, 119, 119);">You can also choose to stop receiving all email from Minds by adjusting your <a style="color:rgb(119, 119, 119);" target="_blank" href="{$siteUrl}settings/canary/account/email-notifications">email settings</a>.</strong>
            HTML;
        } catch (\Exception $e) {
            echo <<<HTML
                <img src="https://d15u56mvtglc6v.cloudfront.net/front/public/assets/logos/medium-production.png" alt="Minds.com" align="middle" width="200px" height="80px"/>
                <h1 style="color:rgb(119, 119, 119);">Sorry, an error has occurred whilst unsubscribing.</h1>
                <strong style="color:rgb(119, 119, 119);">Please manually adjust your <a style="color:rgb(119, 119, 119);" target="_blank" href="{$siteUrl}settings/canary/account/email-notifications">email settings</a>.</strong>
            HTML;
        }
    }
    
    public function post($pages)
    {
    }
    
    public function put($pages)
    {
    }
    
    public function delete($pages)
    {
    }
}
