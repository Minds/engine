<?php

/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Feeds\Activity;

use Exception;
use Minds\Common\EntityMutation;
use Minds\Common\Urn;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Exceptions\CreateActivityFailedException;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Session;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindOffchainPaymentFailedException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentCaptureFailedException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentFailedException;
use Minds\Core\Supermind\Exceptions\SupermindRequestAcceptCompletionException;
use Minds\Core\Supermind\Exceptions\SupermindRequestCreationCompletionException;
use Minds\Core\Supermind\Exceptions\SupermindRequestDeleteException;
use Minds\Core\Supermind\Exceptions\SupermindRequestExpiredException;
use Minds\Core\Supermind\Exceptions\SupermindRequestIncorrectStatusException;
use Minds\Core\Supermind\Exceptions\SupermindRequestStatusUpdateException;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Core\Supermind\Validators\SupermindReplyValidator;
use Minds\Core\Supermind\Validators\SupermindRequestValidator;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\UserCashSetupException;
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

    private SupermindManager $supermindManager;

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
        private ?TitleLengthValidator $titleLengthValidator = null
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
    }

    public function getSupermindManager(): SupermindManager
    {
        return $this->supermindManager ??= Di::_()->get("Supermind\Manager");
    }

    /**
     * Add an activity
     * @param Activity $activity
     * @param bool $fromV2Controller
     * @return bool
     * @throws UnverifiedEmailException
     * @throws StopEventException
     * @throws Exception
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

        // Before add delegates
        if (!$fromV2Controller) {
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
     * @throws ApiErrorException
     * @throws CreateActivityFailedException
     * @throws LockFailedException
     * @throws StopEventException
     * @throws SupermindPaymentIntentFailedException
     * @throws UnverifiedEmailException
     * @throws UserErrorException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ForbiddenException
     * @throws SupermindOffchainPaymentFailedException
     * @throws SupermindRequestDeleteException
     * @throws ServerErrorException
     * @throws Exception
     */
    public function addSupermindRequest(array $supermindDetails, Activity $activity): bool
    {
        $this->getSupermindManager();
        $validator = new SupermindRequestValidator();

        if (!$validator->validate($supermindDetails)) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $validator->getErrors()
            );
        }
        try {
            $receiverGuid = $supermindDetails['supermind_request']['receiver_guid'];
            $receiverUser = is_numeric($receiverGuid) ?
                $this->entitiesBuilder->single($receiverGuid) :
                $this->entitiesBuilder->getByUserByIndex($receiverGuid);

            if (!($receiverUser instanceof User)) {
                throw new UserErrorException(
                    message: "An error was encountered whilst validating the request",
                    code: 400,
                    errors: (new ValidationErrorCollection())->add(
                        new ValidationError(
                            "supermind_request:receiver_guid",
                            "The receiving user for the Supermind request could not be found"
                        )
                    )
                );
            }
        } catch (Exception $e) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: (new ValidationErrorCollection())->add(
                    new ValidationError(
                        "supermind_request:receiver_guid",
                        "The receiving user for the Supermind request could not be found"
                    )
                )
            );
        }

        $paymentMethodId = $supermindDetails['supermind_request']['payment_options']['payment_method_id'] ?? null;

        $supermindRequest = (new SupermindRequest())
            ->setGuid(Guid::build())
            ->setSenderGuid((string)$activity->owner_guid)
            ->setReceiverGuid((string) $receiverUser->getGuid())
            ->setReplyType($supermindDetails['supermind_request']['reply_type'])
            ->setTwitterRequired($supermindDetails['supermind_request']['twitter_required'])
            ->setPaymentAmount($supermindDetails['supermind_request']['payment_options']['amount'])
            ->setPaymentMethod($supermindDetails['supermind_request']['payment_options']['payment_type']);

        $this->supermindManager->setUser(Session::getLoggedinUser());

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
            throw new CreateActivityFailedException();
        }

        return true;
    }

    /**
     * @param array $supermindDetails
     * @param Activity $activity
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws LockFailedException
     * @throws ServerErrorException
     * @throws StopEventException
     * @throws SupermindNotFoundException
     * @throws SupermindPaymentIntentCaptureFailedException
     * @throws SupermindRequestAcceptCompletionException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
     * @throws SupermindRequestStatusUpdateException
     * @throws UnverifiedEmailException
     * @throws UserErrorException
     * @throws UserCashSetupException
     */
    public function addSupermindReply(array $supermindDetails, Activity $activity): bool
    {
        $this->getSupermindManager();
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

        if (!$activity->isQuotedPost()) {
            throw new UserErrorException('Supermind replies must contain content');
        }

        $this->supermindManager->setUser(Session::getLoggedinUser());

        $isSupermindReplyProcessed = $this->supermindManager->acceptSupermindRequest($supermindDetails['supermind_reply_guid']);

        if (!$isSupermindReplyProcessed) {
            throw new UserErrorException(
                message: "An error was encountered whilst accepting the Supermind request",
                code: 400
            );
        }

        $activity->setSupermind([
            'request_guid' => $supermindDetails['supermind_reply_guid'],
            'is_reply' => true
        ]);

        $isActivityCreated = $this->add($activity);

        if (!$isActivityCreated) {
            $this->supermindManager->updateSupermindRequestStatus($supermindDetails['supermind_reply_guid'], SupermindRequestStatus::CREATED);
            throw new SupermindRequestAcceptCompletionException();
        }

        $this->supermindManager->completeAcceptSupermindRequest($supermindDetails['supermind_reply_guid'], $activity->getGuid());

        return true;
    }

    /**
     * Delete activity
     * @param Activity $activity
     * @return bool
     * @throws Exception
     */
    public function delete(Activity $activity): bool
    {
        if (!$activity->canEdit()) {
            throw new Exception('Invalid permission to delete this activity post');
        }

        $success = $this->delete->setEntity($activity)->delete();

        if ($success) {
            $this->metricsDelegate->onDelete($activity);
        }

        return $success;
    }

    /**
     * Get by urn
     * @param string $urn
     * @return Activity|null
     * @throws Exception
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
        /** @var Activity */
        $activity = $activityMutation->getMutatedEntity();

        $this->validateStringLengths($activity);

        /** @var string[] */
        $mutatedAttributes = [];

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
            throw new Exception('Invalid permission to edit this activity post');
        }

        $activity->setEdited(true);

        $activity->indexes = ["activity:$activity->owner_guid:edits"]; //don't re-index on edit

        $this->translationsDelegate->onUpdate($activity);

        if ($activityMutation->hasMutated('timeCreated')) {
            $this->timeCreatedDelegate->onUpdate($activityMutation->getOriginalEntity(), $activity->getTimeCreated(), $activity->getTimeSent());
        
            $mutatedAttributes[] = 'time_created';
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

            $mutatedAttributes[] = 'blurb';
            $mutatedAttributes[] = 'perma_url';
            $mutatedAttributes[] = 'thumbnail_src';

            if (!$activityMutation->hasMutated('title')) {
                $activity->setTitle('');
                $mutatedAttributes[] = 'title';
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
            ->withMutatedAttributes($mutatedAttributes)
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
     * @throws UserErrorException - if activity does not have a message or attachments.
     * @return boolean true if the string lengths are within valid bounds.
     */
    private function validateStringLengths(Activity $activity): bool
    {
        // If not a remind, MUST have either attachments, thumbnail, a message or a title.
        $hasText = strlen((string) $activity->getMessage()) > 0 || strlen((string) $activity->getTitle()) > 0;
        if (!$activity->isRemind() && !$activity->hasAttachments() && !$activity->getThumbnail() && !$hasText) {
            throw new UserErrorException('Activities must have either attachments, a thumbnail or a message');
        }
        // @throws StringLengthException
        $this->messageLengthValidator->validate($activity->getMessage() ?? '', nameOverride: 'post');
        $this->titleLengthValidator->validate($activity->getTitle() ?? '');
        return true;
    }
}
