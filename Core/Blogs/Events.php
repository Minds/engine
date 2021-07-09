<?php

namespace Minds\Core\Blogs;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Session;
use Minds\Core\Blogs\Manager;

class Events
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var Manager $manager */
    private $manager;

    public function __construct($eventsDispatcher = null, $manager = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->manager = $manager ?? new Manager;
    }

    public function register()
    {
        // Entities Builder
        $this->eventsDispatcher->register('entities:map', 'all', function (Event $event) {
            $params = $event->getParameters();

            if ($params['row']->type == 'object' && $params['row']->subtype == 'blog') {
                $blog = (new Legacy\Entity())->build($params['row']);
                $blog->setEphemeral(false);

                $event->setResponse($blog);
            }
        });

        // Entity save

        $this->eventsDispatcher->register('entity:save', 'object:blog', function (Event $event) {
            $blog = $event->getParameters()['entity'];
            $manager = Di::_()->get('Blogs\Manager');
            $event->setResponse($manager->update($blog));
        });

        $this->eventsDispatcher->register('export:extender', 'blog', function (Event $event) {
            $params = $event->getParameters();
            /** @var Core\Blogs\Blog $blog */
            $blog = $params['entity'];
            $export = $event->response() ?: [];
            $currentUser = Session::getLoggedInUserGuid();

            if ($blog->isPaywall() && $blog->owner_guid != $currentUser && !$blog->isPayWallUnlocked()) {
                $export['description'] = '';
                $export['body'] = '';
            } else {
                $export['description'] = $this->manager->signImages($blog->getBody());
            }

            return $event->setResponse($export);
        });
    }
}
