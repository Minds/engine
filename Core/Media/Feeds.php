<?php

namespace Minds\Core\Media;

use Minds\Core;
use Minds\Entities;
use Minds\Helpers;

class Feeds
{
    protected $entity;
    protected $propagateProperties;

    public function __construct(Core\Entities\PropagateProperties $propagateProperties = null)
    {
        $this->propagateProperties = $propagateProperties ?? Core\Di\Di::_()->get('PropagateProperties');
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function updateActivities()
    {
        if (!$this->entity) {
            throw new \Exception('Entity not set');
        }

        $this->propagateProperties->from($this->entity);
    }

    public function dispatch(array $targets = [])
    {
        $targets = array_merge([
            'facebook' => false,
            'twitter' => false
        ], $targets);

        Core\Events\Dispatcher::trigger('social', 'dispatch', [
            'entity' => $this->entity,
            'services' => [
                'facebook' => $targets['facebook'],
                'twitter' => $targets['twitter']
            ],
            'data' => [
                'message' => $this->entity->title,
                'thumbnail_src' => $this->entity->getIconUrl(),
                'perma_url' => $this->entity->getURL()
            ]
        ]);

        return true;
    }
}
