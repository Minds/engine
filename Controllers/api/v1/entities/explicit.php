<?php
/**
 * Minds Newsfeed API
 *
 * @version 1
 * @author Nicolas Ronchi
 */
namespace Minds\Controllers\api\v1\entities;

use Minds\Api\Factory;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Session;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Helpers;
use Minds\Core\Queue\Client as Queue;
use Minds\Core;

class explicit implements Interfaces\Api
{
    private Save $save;

    public function __construct()
    {
        $this->save = new Save();
    }

    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * Sets the activity as explicit
     * @param array $pages
     *
     * API:: /v1/newsfeed/explicit
     */
    public function post($pages)
    {
        $entity = Entities\Factory::build($pages[0]);

        if (!$entity->canEdit()) {
            return Factory::response(['status' => 'error', 'message' => 'Can´t edit this Post']);
        }

        $value = (bool) $_POST['value'];

        $entity->setRating($value ? 3 : 2);

        if ($entity->type === 'user') {
            $matureLock = $entity->getMatureLock();
            $isAdmin = Session::isAdmin();

            if ($matureLock && !$isAdmin) {
                return Factory::response([
                     'status' => 'error',
                     'message' => 'You can not remove the mature flag from your channel',
                 ]);
            }

            $entity->setMature($value);
            
            if ($isAdmin) {
                $entity->setMatureLock($value);

                if ($value) {
                    Queue::build()
                        ->setQueue('MatureBatch')
                        ->send([
                            "user_guid" => $entity->guid,
                            "value" => $value
                        ]);
                }
            }
        } else {
            // mature locked channels are not allowed to remove explicit
            if ($value === false && Session::getLoggedInUser()->getMatureLock()) {
                return Factory::response(['status' => 'error', 'message' => 'You can not remove the explicit flag']);
            }

            if (Helpers\MagicAttributes::setterExists($entity, 'setMature')) {
                $entity->setMature($value);
            } elseif (method_exists($entity, 'setFlag')) {
                $entity->setFlag('mature', $value);
            }
            if (isset($entity->mature)) {
                $entity->mature = $value;
            }

            if (isset($entity->custom_data['mature'])) {
                $entity->custom_data['mature'] = $entity->getMature();
            }

            if (isset($entity->custom_data[0]['mature'])) {
                $entity->custom_data[0]['mature'] = $entity->getMature();
            }

            if ($entity->entity_guid) {
                $attachment = Entities\Factory::build($entity->entity_guid);

                if ($attachment && $attachment->guid && $attachment instanceof Interfaces\Flaggable) {
                    if (method_exists($attachment, 'setMature')) {
                        $attachment->setMature($value);
                    } elseif (method_exists($attachment, 'setFlag')) {
                        $attachment->setFlag('mature', $value);
                    }
                    if (isset($attachment->mature)) {
                        $attachment->mature = $value;
                    }
                    $this->save->setEntity($attachment)->save();
                }
            }
        }

        $save = new Save();
        $saved = $save->setEntity($entity)
            ->save();

        $response = [ 'done' => (bool) $saved ];

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
