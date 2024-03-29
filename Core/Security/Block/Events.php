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
        $this->manager = $manager;
    }

    /**
     * Registers events that the block system hooks into
     */
    public function register(): void
    {
        Dispatcher::register('acl:interact', 'all', function ($e) {
            if (!$this->manager) {
                $this->manager =  Di::_()->get('Security\Block\Manager');
            }

            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];
            $interaction = $params['interaction'];

            $blockEntry = (new BlockEntry())
                ->setActor($user)
                ->setSubject($entity);

            // If user is blocked, don't allow interaction
            // (unless it's a downvote)
            if ($this->manager->isBlocked($blockEntry)) {
                if ($interaction !== 'votedown') {
                    return $e->setResponse(false);
                }
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
            if (!$this->manager) {
                $this->manager =  Di::_()->get('Security\Block\Manager');
            }

            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!$user || !$entity) {
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
