<?php

/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Feeds\Activity;

use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Common\EntityMutation;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Common\Urn;

class Manager
{
    /** @var Delegates\ForeignEntityDelegate */
    private $foreignEntityDelegate;

    /** @var Delegates\TranslationsEntityDelegate */
    private $translationsDelegate;

    /** @var Delegates\AttachmentDelegate */
    private $attachmentDelegate;

    /** @var Delegates\TimeCreatedDelegate */
    private $timeCreatedDelegate;

    /** @var Delegates\VideoPosterDelegate */
    private $videoPosterDelegate;

    /** @var Delegates\PaywallDelegate */
    private $paywallDelegate;

    /** @var Delegates\MetricsDelegate */
    private $metricsDelegate;

    /** @var Save */
    private $save;

    /** @var Delete */
    private $delete;

    /** @var PropagateProperties */
    private $propagateProperties;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct(
        $foreignEntityDelegate = null,
        $translationsDelegate = null,
        $attachmentDelegate = null,
        $timeCreatedDelegate = null,
        $save = null,
        $delete = null,
        $propagateProperties = null,
        $videoPosterDelegate = null,
        $paywallDelegate = null,
        $metricsDelegate = null,
        $entitiesBulder = null
    ) {
        $this->foreignEntityDelegate = $foreignEntityDelegate ?? new Delegates\ForeignEntityDelegate();
        $this->translationsDelegate = $translationsDelegate ?? new Delegates\TranslationsDelegate();
        $this->attachmentDelegate = $attachmentDelegate ?? new Delegates\AttachmentDelegate();
        $this->timeCreatedDelegate = $timeCreatedDelegate ?? new Delegates\TimeCreatedDelegate();
        $this->save = $save ?? new Save();
        $this->delete = $delete ?? new Delete();
        $this->propagateProperties = $propagateProperties ?? new PropagateProperties();
        $this->videoPosterDelegate = $videoPosterDelegate ?? new Delegates\VideoPosterDelegate();
        $this->paywallDelegate = $paywallDelegate ?? new Delegates\PaywallDelegate();
        $this->metricsDelegate = $metricsDelegate ?? new Delegates\MetricsDelegate();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Add an activity
     * @param Activity $activity
     * @return bool
     */
    public function add(Activity $activity): bool
    {
        // Ensure reminds & quoted posts inherit the NSFW settings
        // NOTE: this is not fool proof. If the original entity changes, we still
        // need to create a feature that will propogate these settings to its child derivatives.
        if ($activity->isRemind() || $activity->isQuotedPost()) {
            $remind = $activity->getRemind();
            $activity->setNsfw(array_merge($remind->getNsfw(), $activity->getNsfw()));
        }

        $success = $this->save
            ->setEntity($activity)
            ->save();

        if ($success) {
            $this->metricsDelegate->onAdd($activity);
        }

        return $success;
    }

    /**
     * Delete activity
     * @param Activity $activity
     * @return bool
     */
    public function delete(Activity $activity): bool
    {
        return $this->delete->setEntity($activity)->delete();
    }

    /**
     * Get by urn
     * @param string $urn
     * @return Activity
     */
    public function getByUrn(string $urn): ?Activity
    {
        $urn = new Urn($urn);
        $guid = $urn->getNss();
        $entity = $this->entitiesBuilder->single($guid);

        if (!$entity instanceof Activity) {
            return null; // TODO throw invalid type exception
        }

        return $entity;
    }

    /**
     * Update the activity entity
     */
    public function update(EntityMutation $activityMutation): void
    {
        $activity = $activityMutation->getMutatedEntity();

        if ($activity->type !== 'activity' && in_array($activity->subtype, [
            'video', 'image'
        ], true)) {
            $this->foreignEntityDelegate->onUpdate($activity, $activityMutation);
            return;
        }

        if ($activity->type !== 'activity') {
            throw new \Exception('Invalid entity type');
        }

        if (!$activity->canEdit()) {
            throw new \Exception('Invalid permission to edit this activity post');
        }

        $activity->setEdited(true);

        $activity->indexes = ["activity:$activity->owner_guid:edits"]; //don't re-index on edit

        $this->translationsDelegate->onUpdate($activity);

        if ($activityMutation->hasMutated('timeCreated')) {
            $this->timeCreatedDelegate->onUpdate($activityMutation->getOriginalEntity(), $activity->getTimeCreated(), $activity->getTimeSent());
        }

        // - Attachment

        if ($activityMutation->hasMutated('entityGuid')) {
            // Edit the attachment, if needed
            $activity = $this->attachmentDelegate
                ->setActor(Session::getLoggedinUser())
                ->onEdit($activity, (string) $activity->getEntityGuid());

            // Clean rich embed
            $activity
                //->setTitle('')
                ->setBlurb('')
                ->setURL('')
                ->setThumbnail('');

            if (!$activityMutation->hasMutated('title')) {
                $activity->setTitle('');
            }
        }

        if ($activityMutation->hasMutated('videoPosterBase64Blob')) {
            $this->videoPosterDelegate->onUpdate($activity);
        }

        if ($activityMutation->hasMutated('wireThreshold')) {
            $this->paywallDelegate->onUpdate($activity);
        }

        $this->save
            ->setEntity($activity)
            ->save();

        $this->propagateProperties->from($activity);
    }

    /**
     * @param \ElggEntity $entity
     * @return Activity
     */
    public function createFromEntity($entity): Activity
    {
        $activity = new Activity();
        $activity->setTimeCreated($entity->getTimeCreated() ?: time());
        $activity->setTimeSent($entity->getTimeCreated() ?: time());
        $activity->setTitle($entity->title);
        $activity->setMessage($entity->description);
        $activity->setFromEntity($entity);
        $activity->owner_guid = $entity->owner_guid;
        $activity->container_guid = $entity->container_guid;
        $activity->access_id = $entity->access_id;
        $activity->setTags($entity->tags ?: []);

        if ($entity->type === 'object' && in_array($entity->subtype, ['image', 'video'], true)) {
            /** @var Video|Image */
            $entity = $media = $entity; // Helper for static analysis
            $activity->setCustom(...$entity->getActivityParameters());
            $activity->setPayWall($entity->getFlag('paywall'));
        }

        if ($entity->subtype === 'blog') {
            /** @var \Minds\Core\Blogs\Blog */
            $entity = $blog = $entity; // Helper for static analysis
            $activity->setTitle($entity->getTitle())
                ->setBlurb(strip_tags($entity->getBody()))
                ->setURL($entity->getURL())
                ->setThumbnail($entity->getIconUrl());
        }

        return $activity;
    }

    /**
     *
     */
    public function getByGuid(string $guid): ?Activity
    {
    }
}
