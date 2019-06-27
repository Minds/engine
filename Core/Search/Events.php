<?php
/**
 * Search events listeners
 */
namespace Minds\Core\Search;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Entities;
use Minds\Traits\Logger;

class Events
{
    use Logger;

    public function register()
    {
        /** @var Core\Events\Dispatcher $dispatcher */
        $dispatcher = Di::_()->get('EventsDispatcher');

        // -- Indexing

        $dispatcher->register('search:index', 'all', function (Event $event) {
            try {
                $params = array_merge([
                    'entity' => null,
                    'immediate' => false
                ], $event->getParameters());

                if (!$params['entity']) {
                    return;
                }

                /** @var Core\Events\Dispatcher $dispatcher */
                $dispatcher = Di::_()->get('EventsDispatcher');

                if ($params['immediate']) {
                    $dispatcher->trigger('search:index:dispatch', 'all', [
                        'entity' => $params['entity']
                    ]);
                } else {
                    $dispatcher->trigger('search:index:queue', 'all', [
                        'entity' => $params['entity']
                    ]);
                }

            } catch (\Exception $e) {
                $this->logger()->error($e);
            }
        });

        // Queue

        $dispatcher->register('search:index:queue', 'all', function (Event $event) {
            try {
                $params = array_merge([
                    'entity' => null
                ], $event->getParameters());

                if (!$params['entity']) {
                    return;
                }

                Di::_()->get('Search\Queue')
                    ->queue($params['entity']);

            } catch (\Exception $e) {
                $this->logger()->error($e);
            }
        });

        // Dispatch (sync or from queue)

        $dispatcher->register('search:index:dispatch', 'all', function (Event $event) {
            try {
                $params = array_merge([
                    'entity' => null
                ], $event->getParameters());

                if (!$params['entity']) {
                    return;
                }

                Di::_()->get('Search\Index')
                    ->index(
                        is_string($params['entity']) ?
                            unserialize($params['entity']) :
                            $params['entity']
                    );

            } catch (\Exception $e) {
                $this->logger()->error($e);
            }
        });

        // -- Cleanup

        $dispatcher->register('search:cleanup', 'all', function (Event $event) {
            try {
                $params = array_merge([
                    'entity' => null,
                    'immediate' => false
                ], $event->getParameters());

                if (!$params['entity']) {
                    return;
                }

                /** @var Core\Events\Dispatcher $dispatcher */
                $dispatcher = Di::_()->get('EventsDispatcher');

                if ($params['immediate']) {
                    $dispatcher->trigger('search:cleanup:dispatch', 'all', [
                        'entity' => $params['entity']
                    ]);
                } else {
                    $dispatcher->trigger('search:cleanup:queue', 'all', [
                        'entity' => $params['entity']
                    ]);
                }

            } catch (\Exception $e) {
                $this->logger()->error($e);
            }
        });

        // Queue cleanup

        $dispatcher->register('search:cleanup:queue', 'all', function (Event $event) {
            try {
                $params = array_merge([
                    'entity' => null
                ], $event->getParameters());

                if (!$params['entity']) {
                    return;
                }

                Di::_()->get('Search\Queue')
                    ->queueCleanup($params['entity']);

            } catch (\Exception $e) {
                $this->logger()->error($e);
            }
        });

        // Dispatch cleanup (sync or from queue)

        $dispatcher->register('search:cleanup:dispatch', 'all', function (Event $event) {
            try {
                $params = array_merge([
                    'entity' => null
                ], $event->getParameters());

                if (!$params['entity']) {
                    return;
                }

                Di::_()->get('Search\Cleanup')
                    ->prune(
                        is_string($params['entity']) ?
                            unserialize($params['entity']) :
                            $params['entity']
                    );

            } catch (\Exception $e) {
                $this->logger()->error($e);
            }
        });

        // Minds hooks

        $entityLifecycleHook = function ($hook, $type, $entity, array $params = []) {
            if (!$entity) {
                return;
            }

            $allowedTypes = [
                'activity',
                'user',
                'group',
                'object:blog',
                'object:image',
                'object:album',
                'object:video'
            ];

            $key = $entity->type;

            if ($entity->subtype) {
                $key .= ':' . $entity->subtype;
            }

            if (in_array($key, $allowedTypes)) {
                /** @var Core\Events\Dispatcher $dispatcher */
                $dispatcher = Di::_()->get('EventsDispatcher');

                $dispatcher->trigger('search:index', $key, [
                    'entity' => $entity
                ]);
            }
        };

        $dispatcher->register('create', 'all', $entityLifecycleHook);
        $dispatcher->register('update', 'all', $entityLifecycleHook);

        // Deletion Cleanup

        $dispatcher->register('delete', 'all', function (Event $event) {
            $params = $event->getParameters();
            $entity = $params['entity'];

            if (!$entity) {
                return;
            }

            $allowedTypes = [
                'activity',
                'user',
                'group',
                'object:blog',
                'object:image',
                'object:album',
                'object:video'
            ];

            $key = $entity->type;

            if ($entity->subtype) {
                $key .= ':' . $entity->subtype;
            }

            if (in_array($key, $allowedTypes)) {
                /** @var Core\Events\Dispatcher $dispatcher */
                $dispatcher = Di::_()->get('EventsDispatcher');

                $dispatcher->trigger('search:cleanup', $key, [
                    'entity' => $entity
                ]);
            }
        });
    }
}
