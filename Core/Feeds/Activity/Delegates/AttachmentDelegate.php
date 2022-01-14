<?php
/**
 * AttachmentDelegate
 *
 * @author edgebal
 */

namespace Minds\Core\Feeds\Activity\Delegates;

use Exception;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Helpers\Log;
use Minds\Interfaces\Flaggable;

/**
 * Class that manages the activity attachment lifecycles (creation, edition, deletion).
 * Called by newsfeed controlled.
 * @package Minds\Core\Feeds\Activity\Delegates
 */
class AttachmentDelegate
{
    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Save */
    protected $saveAction;

    /** @var Delete */
    protected $deleteAction;

    /** @var User */
    protected $actor;

    /**
     * AttachmentDelegate constructor.
     * @param $config
     * @param $entitiesBuilder
     * @param $saveAction
     * @param $deleteAction
     */
    public function __construct(
        $config = null,
        $entitiesBuilder = null,
        $saveAction = null,
        $deleteAction = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->saveAction = $saveAction ?: new Save();
        $this->deleteAction = $deleteAction ?: new Delete();
    }

    /**
     * Sets the current actor (user which is making the changes)
     * @param User|null $actor
     * @return AttachmentDelegate
     */
    public function setActor(?User $actor): AttachmentDelegate
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @param Activity $activity
     * @param string $attachmentGuid
     * @return Activity
     * @throws Exception
     */
    public function onCreate(Activity $activity, string $attachmentGuid): Activity
    {
        /** @var Image|Video $attachment */
        $attachment = $this->entitiesBuilder->single($attachmentGuid);

        if (!$attachment) {
            throw new Exception('Media attachment does not exist');
        }

        if (
            !$this->actor->isAdmin() &&
            ((string) $attachment->owner_guid !== (string) $this->actor->guid)
        ) {
            throw new Exception('You have no permissions to use this media attachment');
        }

        $attachment->title = $activity->title;
        $attachment->setDescription($activity->getMessage());
        $attachment->container_guid = $activity->getContainerGUID();
        $attachment->access_id = $activity->getAccessID();

        if ($activity->license) {
            $attachment->license = $activity->license;
        }

        if ($attachment instanceof Flaggable) {
            $attachment->setFlag('mature', $activity->getMature());
        }

        if ($activity->isPaywall()) {
            $attachment->access_id = 0;
            $attachment->hidden = true;

            $attachment->setPayWall(true);

            if (method_exists($attachment, 'setWireThreshold')) {
                $attachment->setWireThreshold($activity->getWireThreshold());
            }
        }

        $attachment
            ->setNsfw($activity->getNsfw());

        $attachment
            ->set('time_created', $activity->getTimeCreated());

        $attachment
            ->setTags($activity->getTags() ?: []);

        switch ($attachment->subtype) {
            case 'image':
                $activity
                    ->setFromEntity($attachment)
                    ->setCustom('batch', [[
                        'src' => sprintf("%sfs/v1/thumbnail/%s", $this->config->get('cdn_url'), $attachment->guid),
                        'href' => sprintf("%smedia/%s/%s", $this->config->get('site_url'), $attachment->container_guid, $attachment->guid),
                        'mature' => $attachment instanceof Flaggable ? $attachment->getFlag('mature') : false,
                        'width' => $attachment->width,
                        'height' => $attachment->height,
                        'blurhash' => $attachment->blurhash,
                        'gif' => (bool) $attachment->gif ?? false,
                    ]]);
                break;
            case 'video':
                $activity
                    ->setFromEntity($attachment)
                    ->setCustom('video', [
                        'thumbnail_src' => $attachment->getIconUrl(),
                        'guid' => $attachment->guid,
                        'width' => $attachment->width,
                        'height' => $attachment->height,
                        'mature' => $attachment instanceof Flaggable ? $attachment->getFlag('mature') : false
                    ]);
                break;
        }

        if ($activity->getPending() && $attachment) {
            $attachment->access_id = 0;
        }

        $this->saveAction
            ->setEntity($attachment)
            ->save();

        return $activity;
    }

    /**
     * Edits the Activity attachment, if any changes. This methods just uses onDelete and onCreate.
     * @param Activity $activity
     * @param string $attachmentGuid
     * @return Activity
     * @throws Exception
     */
    public function onEdit(Activity $activity, string $attachmentGuid): Activity
    {
        $currentEntityGuid = (string) $activity->getEntityGuid();

        if ($currentEntityGuid === $attachmentGuid) {
            // Entity not changed, move along
            return $activity;
        }

        $activity = $this->onDelete($activity);

        if (!$attachmentGuid) {
            // If no replacement, move along
            return $activity;
        }

        return $this->onCreate($activity, $attachmentGuid);
    }

    /**
     * Deletes the Activity attachment, only if it belongs to the same user
     * @param Activity $activity
     * @return Activity
     */
    public function onDelete(Activity $activity): Activity
    {
        $ownerGuid = $activity->getOwnerGuid();

        /** @var string|int|null $attachmentGuid */
        $attachmentGuid = $activity->getEntityGuid();

        if (
            $attachmentGuid &&
            in_array($activity->custom_type, ['batch', 'video'], true) // Only images and videos
        ) {
            try {
                // Get the attachment entity
                $attachment = $this->entitiesBuilder->single($attachmentGuid);

                // Only delete attachments if they exist and it belongs to the Activity owner
                if ($attachment && (string) $ownerGuid === (string) $attachment->owner_guid) {
                    $this->deleteAction
                        ->setEntity($attachment)
                        ->delete();
                }
            } catch (Exception $e) {
                Log::error(sprintf("Cannot delete activity %s attachment: %s", $activity->guid, $attachmentGuid));
            }
        }

        // Set the activity entity GUID to an empty value, no matter what happened above
        $activity->setEntityGuid(null);

        // Empties the activity entity's custom meta attributes, no matter what happened above
        $activity->setCustom(null, null);

        return $activity;
    }
}
