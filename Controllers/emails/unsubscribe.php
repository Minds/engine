<?php
/**
 * Minds email unsubscribe folder controller.
 */
namespace Minds\Controllers\emails;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Interfaces;

class unsubscribe extends core\page implements Interfaces\page
{
    /**
     * Get requests
     */
    public function get($pages)
    {
        ACL::_()->setIgnore(true);

        $siteUrl = Core\Config::_()->site_url;

        try {
            $campaign = strtolower($pages[2]);
            $topic = strtolower($pages[3]);
            $email = strtolower(urldecode($pages[1]));
            $userGuid = strtolower($pages[0]);
            $user = Di::_()->get(EntitiesBuilder::class)->single($userGuid);

            if ($user->getEmail() == $email) {
                /** @var Core\Email\Manager $manager */
                $manager = Core\Di\Di::_()->get('Email\Manager');

                $manager->unsubscribe($user, [ $campaign ], [ $topic ]);
            } else {
                throw new \Exception('UnsubscribeSaveException');
            }

            echo <<<HTML
                <h1 style="color:rgb(119, 119, 119);">SUCCESS. You won't receive any more emails like this for @$user->username</h1>
                <strong style="color:rgb(119, 119, 119);">You can also choose to stop receiving all email by adjusting your <a style="color:rgb(119, 119, 119);" target="_blank" href="{$siteUrl}settings/canary/account/email-notifications">email settings</a>.</strong>
            HTML;
        } catch (\Exception $e) {
            echo <<<HTML
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
