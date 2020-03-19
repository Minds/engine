<?php
/**
 */

 namespace Minds\Core\Reports;

use Minds\Core;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Di\Di;

class Events
{
    public function register()
    {
        Di::_()->get('EventsDispatcher')->register('ban', 'user', function ($event) {
            $config = Di::_()->get('Config');
            $user = $event->getParameters();
            //send ban email
            $template = new Core\Email\Template();
            $template
                ->setTemplate()
                ->setBody('banned.tpl')
                ->set('username', $user->username)
                ->set('email', $user->getEmail())
                ->set('reason', $user->ban_reason)
                ->set('user', $user);

            $message = new Core\Email\Message();
            $message->setTo($user)
                ->setMessageId(implode('-', [$user->guid, sha1($user->getEmail()), sha1('register-' . time())]))
                ->setSubject("You are banned from Minds.")
                ->setReplyTo($config->get('contact_details')['email'], $config->get('contact_details')['name'])
                ->setHtml($template);
            Di::_()->get('Mailer')->queue($message);

            // Record metric

            $event = new Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setUserGuid((string) Core\Session::getLoggedInUser()->guid)
                ->setEntityGuid((string) $user->getGuid())
                ->setAction("ban")
                ->setBanReason($user->ban_reason)
                ->push();
        });
    }
}
