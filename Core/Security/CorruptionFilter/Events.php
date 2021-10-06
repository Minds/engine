<?php
namespace Minds\Core\Security\CorruptionFilter;

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
     * Registers events that the corruptionFilter manager hooks into
     */
    public function register(): void
    {
        Dispatcher::register('acl:read:blacklist', 'all', function ($e) {
            if (!$this->manager) {
                $this->manager =  Di::_()->get('Security\CorruptionFilter\Manager');
            }

            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if ($this->manager->isCorrupted($entity)) {
                $e->setResponse(true);
            } else {
                $e->setResponse(false);
            }
        });
    }
}
