<?php
namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;

class EventsResolverDelegate implements ResolverDelegate
{
    /**
     * @var EventsDispatcher
     */
    protected $eventsDispatcher;

    /**
     * @param EventsDispatcher $eventsDispatcher
     */
    public function __construct(EventsDispatcher $eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * @param Urn $urn
     * @return boolean
     */
    public function shouldResolve(Urn $urn): bool
    {
        // This is NOT ideal, we shouldn't need to modify this file at all
        return in_array($urn->getNid(), [
            'wire',
            'withdraw-request',
            'peer-boost',
        ], true);
    }

    /**
     * @param array $urns
     * @param array $opts
     * @return array
     */
    public function resolve(array $urns, array $opts = []): ?array
    {
        $entities = [];

        foreach ($urns as $urn) {
            $entity = $this->eventsDispatcher->trigger('urn:resolve', 'all', [ 'urn' => $urn ], null);
            if ($entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * @param string $urn
     * @param mixed $entity
     * @return mixed
     */
    public function map($urn, $entity)
    {
        return $entity;
    }

    /**
     * @param mixed $entity
     * @return string|null
     */
    public function asUrn($entity): ?string
    {
        if (!$entity) {
            return null;
        }

        if (method_exists($entity, 'getUrn')) {
            return $entity->getUrn();
        }

        return null;
    }
}
