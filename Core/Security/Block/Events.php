<?php
namespace Minds\Core\Security\Block;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;

class Events
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?? Di::_()->get('Security\Block\Manager');
    }

    /**
     * Registers events that the block system hooks into
     */
    public function register(): void
    {
        Dispatcher::register('acl:interact', 'all', function ($e) {
            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            $blockEntry = (new BlockEntry())
                ->setActor($user)
                ->setSubject($entity);

            // If user is blocked, don't allow interaction
            if ($this->manager->isBlocked($blockEntry)) {
                return $e->setResponse(false);
            }

            // If user has blocked, don't allow i
            if ($this->manager->hasBlocked($blockEntry)) {
                return $e->setResponse(false);
            }
        });

        /**
         * Returning true below will prohibit the entity from being read
         */
        Dispatcher::register('acl:read:blacklist', 'all', function ($e) {
            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!$user) {
                return;
            }

            $blockEntry = (new BlockEntry())
                ->setActor($user)
                ->setSubject($entity);

            // If user has blocked, don't allow it to be read
            if ($this->manager->hasBlocked($blockEntry)) {
                return $e->setResponse(true);
            }
        });
    }
}
