<?php

/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Feeds\Activity;

use Minds\Common\EntityMutation;
use Minds\Common\Urn;
use Minds\Core\Boost\Network\ElasticRepository as BoostElasticRepository;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\GuidLinkResolver;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Exceptions\CreateActivityFailedException;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Session;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentFailedException;
use Minds\Core\Supermind\Exceptions\SupermindRequestCreationCompletionException;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Validators\SupermindReplyValidator;
use Minds\Core\Supermind\Validators\SupermindRequestValidator;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\EntityInterface;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\UserErrorException;
use Minds\Helpers\StringLengthValidators\MessageLengthValidator;
use Minds\Helpers\StringLengthValidators\TitleLengthValidator;
use Stripe\Exception\ApiErrorException;

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

    /** @var Delegates\NotificationsDelegate */
    private $notificationsDelegate;

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
        $notificationsDelegate = null,
        $entitiesBuilder = null,
        private ?MessageLengthValidator $messageLengthValidator = null,
        private ?TitleLengthValidator $titleLengthValidator = null,
        private ?BoostElasticRepository $boostRepository = null,
        private ?GuidLinkResolver $guidLinkResolver = null,
        private ?SupermindManager $supermindManager = null
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
        $this->notificationsDelegate = $notificationsDelegate ?? new Delegates\NotificationsDelegate();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->messageLengthValidator = $messageLengthValidator ?? new MessageLengthValidator();
        $this->titleLengthValidator = $titleLengthValidator ?? new TitleLengthValidator();
        $this->boostRepository ??= new BoostElasticRepository();
        $this->guidLinkResolver ??= new GuidLinkResolver();
        $this->supermindManager ??= Di::_()->get("Supermind\Manager");
    }

    /**
     * Add an activity
     * @param Activity $activity
     * @param bool $fromV2Controller
     * @return bool
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    public function add(Activity $activity, bool $fromV2Controller = false): bool
    {
        $this->validateStringLengths($activity);

        // Ensure reminds & quoted posts inherit the NSFW settings
        // NOTE: this is not fool proof. If the original entity changes, we still
        // need to create a feature that will propogate these settings to its child derivatives.
        if ($activity->isRemind() || $activity->isQuotedPost()) {
            $remind = $activity->getRemind();
            if (!$remind) {
                return false; // Can not save a remind where the original post doesn't exist
            }
            $activity->setNsfw(array_merge($remind->getNsfw(), $activity->getNsfw()));
        }
        
        // Before add delegattes
        if (!$fromV2Controller) {
            //$this->timeCreatedDelegate->beforeAdd($activity, );
            $this->paywallDelegate->beforeAdd($activity);
        }

        $success = $this->save
            ->setEntity($activity)
            ->save();

        if ($success) {
            $this->metricsDelegate->onAdd($activity);
            $this->notificationsDelegate->onAdd($activity);
        }

        return $success;
    }

    /**
     * @param array $supermindDetails
     * @param Activity $activity
     * @return bool
     * @throws CreateActivityFailedException
     * @throws StopEventException
     * @throws UnverifiedEmailException
     * @throws UserErrorException
     * @throws LockFailedException
     * @throws SupermindPaymentIntentFailedException
     * @throws ApiErrorException
     */
    public function addSupermindRequest(array $supermindDetails, Activity $activity): bool
    {
        $validator = new SupermindRequestValidator();

        if (!$validator->validate($supermindDetails)) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $validator->getErrors()
            );
        }

        $paymentMethodId = $supermindDetails['supermind_request']['payment_options']['payment_method_id'] ?? null;

        $supermindRequest = (new SupermindRequest())
            ->setGuid(Guid::build())
            ->setSenderGuid((string)$activity->owner_guid)
            ->setReceiverGuid((string) $supermindDetails['supermind_request']['receiver_guid'])
            ->setReplyType($supermindDetails['supermind_request']['reply_type'])
            ->setTwitterRequired($supermindDetails['supermind_request']['twitter_required'])
            ->setPaymentAmount($supermindDetails['supermind_request']['payment_options']['amount'])
            ->setPaymentMethod($supermindDetails['supermind_request']['payment_options']['payment_type']);

        $isSupermindRequestCreated = $this->supermindManager->addSupermindRequest($supermindRequest, $paymentMethodId);

        if (!$isSupermindRequestCreated) {
            throw new CreateActivityFailedException();
        }

        $activity->setSupermind([
            'request_guid' => $supermindRequest->getGuid(),
            'is_reply' => false
        ]);

        $isActivityCreated = $this->add($activity, true);

        if (!$isActivityCreated) {
            $this->supermindManager->deleteSupermindRequest($supermindRequest->getGuid());
            throw new CreateActivityFailedException();
        }

        try {
            $this->supermindManager->completeSupermindRequestCreation($supermindRequest->getGuid(), $activity->getGuid());
        } catch (SupermindRequestCreationCompletionException $e) {
            $this->delete($activity);
            $this->supermindManager->deleteSupermindRequest($supermindRequest->getGuid());
        }

        return true;
    }

    /**
     * @param array $supermindDetails
     * @param Activity $activity
     * @return bool
     * @throws ApiErrorException
     * @throws CreateActivityFailedException
     * @throws LockFailedException
     * @throws StopEventException
     * @throws UnverifiedEmailException
     * @throws UserErrorException
     * @throws SupermindNotFoundException
     */
    public function addSupermindReply(array $supermindDetails, Activity $activity): bool
    {
        $validator = new SupermindReplyValidator();

        if (
            !$validator->validate(
                array_merge(
                    $supermindDetails,
                    [
                        'activity' => $activity
                    ]
                )
            )
        ) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $validator->getErrors()
            );
        }

        $activity->setSupermind([
            'request_guid' => $supermindDetails['supermind_reply_guid'],
            'is_reply' => true
        ]);
        
        $isActivityCreated = $this->add($activity);

        if (!$isActivityCreated) {
            throw new CreateActivityFailedException();
        }

        return $this->supermindManager->acceptSupermindRequest($supermindDetails['supermind_reply_guid']);
    }

    /**
     * Delete activity
     * @param Activity $activity
     * @return bool
     */
    public function delete(Activity $activity): bool
    {
        $success = $this->delete->setEntity($activity)->delete();

        if ($success) {
            $this->metricsDelegate->onDelete($activity);
        }

        return $success;
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
     * Update the activity entity.
     * @throws UserErrorException
     * @throws \Exception
     */
    public function update(EntityMutation $activityMutation): bool
    {
        $activity = $activityMutation->getMutatedEntity();

        $this->validateStringLengths($activity);

        if ($this->isActivelyBoostedEntity($activityMutation->getOriginalEntity())) {
            throw new UserErrorException('Sorry, you can not edit a post with a Boost in progress.');
        }

        if ($activity->type !== 'activity' && in_array($activity->subtype, [
            'video', 'image'
        ], true)) {
            $this->foreignEntityDelegate->onUpdate($activity, $activityMutation);
            return true;
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

        $success = $this->save
            ->setEntity($activity)
            ->save();

        // Will no longer be relevant for new media posts without entity_guid
        $this->propagateProperties->from($activity);

        return $success;
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
        $activity->setNsfw($entity->getNsfw());
        $activity->setNsfwLock($entity->getNsfwLock());
        $activity->owner_guid = $entity->owner_guid;
        $activity->container_guid = $entity->container_guid;
        $activity->access_id = $entity->access_id;
        $activity->setTags($entity->tags ?: []);

        if ($entity->type === 'object' && in_array($entity->subtype, ['image', 'video'], true)) {
            /** @var Video|Image */
            $entity = $media = $entity; // Helper for static analysis
            $activity->setCustom(...$entity->getActivityParameters());
            $activity->setPayWall($entity->isPayWall());
            $activity->setWireThreshold($entity->getWireThreshold());
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
     * Will update entity attachments (post entity_guid multi image assets)
     * @param Activity $activity
     * @param EntityInterface $entity
     * @return bool
     */
    public function patchAttachmentEntity(Activity $activity, EntityInterface $entity): bool
    {
        return $this->save->setEntity($entity)->save();
    }

    /**
     * TODO
     */
    public function getByGuid(string $guid): ?Activity
    {
        return null;
    }

    /**
     * Assert that the string lengths are within valid bounds.
     * @param Activity $activity - activity to check.
     * @throws StringLengthValidator - if the string lengths are invalid.
     * @return boolean true if the string lengths are within valid bounds.
     */
    private function validateStringLengths(Activity $activity): bool
    {
        // @throws StringLengthException
        $this->messageLengthValidator->validate($activity->getMessage() ?? '', nameOverride: 'post');
        $this->titleLengthValidator->validate($activity->getTitle() ?? '');
        return true;
    }

    /**
     * Checks whether an entity is currently actively boosted.
     * @param Entity $entity - entity to check.
     * @return boolean - true if the entity has an actively boosted state.
     */
    private function isActivelyBoostedEntity(Entity $entity): bool
    {
        $originalGuid = (string) $entity->getGuid();

        if ($linkedGuid = $this->getLinkedGuid($entity)) {
            $entityGuidArray = [
                $originalGuid,
                $linkedGuid,
            ];
        }

        $results = $this->boostRepository->getList([
            // can pass an array to do a terms query with multiple guids.
            'entity_guid' => $entityGuidArray ?? $originalGuid,
            'state' => 'approved'
        ]);

        return $results && count($results) > 0;
    }

    /**
     * Gets a linked GUID for an entity. Passing an activity will
     * give you the entity guid and vice-versa.
     * @param Entity|Activity $entity - the entity to get the linked guid for.
     * @return ?string - linked guid.
     */
    private function getLinkedGuid(Entity|Activity $entity): ?string
    {
        $originalGuid = (string) $entity->getGuid();
        $entityGuid = (string) $entity->getEntityGuid() ?? false;

        if ($entityGuid && $originalGuid !== $entityGuid) {
            return $entityGuid;
        }
        return $this->guidLinkResolver->resolve($originalGuid);
    }
}
