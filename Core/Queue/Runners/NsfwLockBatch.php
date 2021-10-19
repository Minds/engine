<?php
namespace Minds\Core\Queue\Runners;

use Minds\Core;
use Minds\Core\Queue;
use Minds\Core\Queue\Interfaces;
use Minds\Entities;
use Minds\Helpers\MagicAttributes;
use Minds\Core\Entities\Actions\Save;
use Minds\Interfaces\Flaggable;
use Minds\Core\Security\ACL;

/**
 * Iteratively removes or adds `nsfw_lock` from a users posts.
 */
class NsfwLockBatch implements Interfaces\QueueRunner
{
    public function __construct()
    {
    }

    /**
     * Run queue. Receives data with params user_guid, and value.
     *
     * user_guid: ID of the user we are acting upon
     * value: numeric nsfw reasons in array (as is stored in nsfw_lock and nsfw fields).
     *
     * Example usage:
     *
     *  $queueClient->setQueue('NsfwLockBatch')
     *    ->send([
     *      'user_guid' => '123',
     *      'value' => ['1','2'],
     *    ]);
     */
    public function run(): void
    {
        ACL::$ignore = true;
        $client = Queue\Client::Build();
        $client->setQueue("NsfwLockBatch")
            ->receive(function ($data) {
                // get data.
                $data = $data->getData();
                $userGuid = $data['user_guid'];
                $value = $data['value'];

                // output receipt message.
                $valueString = json_encode($value);
                echo "Received a request to set nsfw_lock for all of the posts from user: $userGuid, to $valueString\n";

                // iterate through entity types.
                foreach (['image', 'video', 'activity'] as $type) {
                    // set owner guid in options to users guid.
                    $options = [
                      'owner_guid' => $userGuid
                    ];

                    // if its an image or video, set the appropriate type and subtype.
                    if ($type == 'image' || $type == 'video') {
                        $options['subtype'] = $type;
                        $type = 'object';
                    }

                    // get entities from database using options.
                    // hard capping at 1000 entities - which should cover posts made in the time a user appeals a decision.
                    $entities = Core\Entities::get(array_merge([
                        'type' => $type,
                        'limit' => 1000,
                        'offset' => '',
                    ], $options));

                    if (!$entities) {
                        continue;
                    }

                    // iterate through entities and set nsfw_lock.
                    foreach ((array)$entities as $entity) {
                        try {
                            $this->setNsfwLock($entity, $value);

                            $entityNsfwLockString = json_encode($entity->getNsfwLock());
                            echo "Set nsfw_lock for post: {$entity->getGuid()}, by user: {$entity->getOwnerGuid()}, to {$entityNsfwLockString}\n";
                        } catch (\Exception $e) {
                            error_log($e);
                            echo "Skipped {$entity->getGuid()} because of the above exception\n";
                        }
                    }
                }
                echo "Finished updating nsfw_lock status of entities for user: $userGuid\n";
            });
    }

    /**
     * @param Entities\Entity $entity - the entity to set / unset the locks on.
     * @param array $value - array to be set as nsfw_lock (e.g. [1, 2, 3]).
     * @return void
     */
    private function setNsfwLock($entity, array $value): void
    {
        if (MagicAttributes::setterExists($entity, 'setNsfwLock')) {
            $entity->setNsfwLock($value);
        } elseif (property_exists($entity, 'nsfw_lock')) {
            $entity->nsfw_lock = $value;
        }
        
        if (property_exists($entity, 'custom_data')) {
            $entity->custom_data['nsfw_lock'] = $entity->getNsfwLock();
            $entity->custom_data[0]['nsfw_lock'] = $entity->getNsfwLock();
        }

        if ($entity->entity_guid) {
            $attachment = Entities\Factory::build($entity->entity_guid);

            if ($attachment && $attachment->guid && $attachment instanceof Flaggable) {
                if (method_exists($attachment, 'setNsfwLock')) {
                    $attachment->setNsfwLock($value);
                } elseif (isset($attachment->nsfw_lock)) {
                    $attachment->nsfw_lock = $value;
                }
                $attachment->save();
            }
        }

        $save = new Save();
        $saved = $save->setEntity($entity)
            ->save();

        if (!$saved) {
            echo "nsfw_lock save failed for post {$entity->getGuid()}, by user {$entity->getOwnerGuid()}\n";
            return;
        }

        Core\Events\Dispatcher::trigger('search:index', 'all', [
            'entity' => $entity
        ]);
    }
}
