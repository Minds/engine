<?php
/**
 * GenerateIdDelegate -
 *
 * Dispatches request to permaweb server to get the seeded id without
 * commiting to the transaction to the Arweave network.
 *
 * @author Ben Hayward
 */
namespace Minds\Core\Permaweb\Delegates;

use Minds\Core\Entities\Actions\Save;
use Minds\Entities\Image;
use Minds\Entities\Video;

class GenerateIdDelegate extends AbstractPermawebDelegate
{
    public function __construct($save = null)
    {
        parent::__construct();
        $this->save = $save ?: new Save();
    }

    /**
     * Dispatch save call.
     */
    public function dispatch(): void
    {
        $id = $this->manager->generateId(
            $this->assembleOpts()
        );

        if (!$id) {
            throw new \Exception('Could not generate a permaweb id. Ensure you can connect to the permaweb node and file size is not too large.');
        }

        $activity = $this->getActivity();
        $activity->setPermawebId($id);

        $this->save->setEntity($activity)->save();

        // if image propegate id to images
        if ($activity->custom_type === 'batch') {
            $image = new Image($activity->entity_guid);
            $image->setPermawebId($id);
            $this->save->setEntity($image)->save();
        }

        // if video propegate id to video
        if ($activity->custom_type === 'video') {
            $video = new Video($activity->entity_guid);
            $video->setPermawebId($id);
            $this->save->setEntity($video)->save();
        }
    }
}
