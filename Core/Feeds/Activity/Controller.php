<?php

namespace Minds\Core\Feeds\Activity;

use Minds\Common\Access;
use Minds\Common\EntityMutation;
use Minds\Core\Blogs\Blog;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Exceptions\CreateActivityFailedException;
use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Monetization\Demonetization\Validators\DemonetizedPlusValidator;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\CreatePaywalledEntityService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentFailedException;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Exceptions\AlreadyPublishedException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        protected ?Manager $manager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?ACL $acl = null,
        protected ?EntityTimeCreated $entityTimeCreated = null,
        protected ?DemonetizedPlusValidator $demonetizedPlusValidator = null,
        protected ?RbacGatekeeperService $rbacGatekeeperService = null,
        protected ?CreatePaywalledEntityService $createPaywalledEntityService = null,
        protected ?Logger $logger = null
    ) {
        $this->manager ??= new Manager();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->acl ??= Di::_()->get('Security\ACL');
        $this->entityTimeCreated ??= new EntityTimeCreated();
        $this->demonetizedPlusValidator ??= Di::_()->get(DemonetizedPlusValidator::class);
        $this->rbacGatekeeperService ??= Di::_()->get(RbacGatekeeperService::class);
        $this->createPaywalledEntityService ??= Di::_()->get(CreatePaywalledEntityService::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * PUT
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws CreateActivityFailedException
     * @throws ServerErrorException
     * @throws UnauthorizedException
     * @throws UserErrorException
     * @throws LockFailedException
     * @throws UnverifiedEmailException
     * @throws SupermindNotFoundException
     * @throws SupermindPaymentIntentFailedException
     * @throws StopEventException
     * @throws ApiErrorException
     */
    public function createNewActivity(ServerRequestInterface $request, Activity $activity = null): JsonResponse
    {
        /** @var User $user */
        $user = $request->getAttribute('_user');

        $payload = $request->getParsedBody();

        $activity ??= new Activity();

        /**
         * NSFW and Mature
         */
        $activity->setMature(isset($payload['mature']) && !!$payload['mature']);
        $activity->setNsfw($payload['nsfw'] ?? []);

        // If a user is mature, their post should be tagged as that too
        if ($user->isMature()) {
            $activity->setMature(true);
        }

        /**
         * Access ID
         */
        if (isset($payload['access_id'])) {
            $activity->setAccessId($payload['access_id']);
        }

        /**
         * Message
         */
        if (isset($payload['message'])) {
            $activity->setMessage(rawurldecode($payload['message']));
        }

        /**
         * Reminds & Quote posts
         */
        $remind = null;
        if (isset($payload['remind_guid'])) {
            // Fetch the remind

            $remind = $this->entitiesBuilder->single($payload['remind_guid']);
            if (!(
                $remind instanceof Activity ||
                $remind instanceof Image ||
                $remind instanceof Video ||
                $remind instanceof Blog
            )) {
                // We should **NOT** allow for the reminding of non-Activity entities,
                // however this is causing client side regressions and they are being cast to activity views
                // This can be revisited once we migrate entirely away from ->entity_guid support.
                throw new UserErrorException("The post your are trying to remind or quote was not found");
            }

            if ((int) $remind->getAccessId() === Access::UNLISTED) {
                throw new UserErrorException("The post your are trying to remind is unlisted and can not be reminded or quoted");
            }

            // throw and error return response if acl interaction check fails.

            if (!$this->acl->interact($remind, $user)) {
                throw new UnauthorizedException();
            }

            $shouldBeQuotedPost = $payload['message'] || (
                is_array($payload['attachment_guids']) &&
                count($payload['attachment_guids'])
            );

            if (!$shouldBeQuotedPost && $this->manager->countRemindsOfEntityByUser($remind, $user) > 0) {
                throw new UserErrorException("You've already reminded this post");
            }


            $remindIntent = new RemindIntent();
            $remindIntent->setGuid($remind->getGuid())
                        ->setOwnerGuid($remind->getOwnerGuid())
                        ->setQuotedPost($shouldBeQuotedPost);

            $activity->setRemind($remindIntent);
        }

        /**
         * Wire/Paywall
         */
        if (isset($payload['wire_threshold']) && $payload['wire_threshold']) {
            // don't allow paywalling a paywalled remind
            if ($remind?->getPaywall()) {
                throw new UserErrorException("You can not monetize a remind or quote post");
            }

            if (isset($payload['wire_threshold']['support_tier']['urn'])) {
                $this->demonetizedPlusValidator->validateUrn(
                    urn: $payload['wire_threshold']['support_tier']['urn'],
                    user: $user
                );
            }

            $activity->setWireThreshold($payload['wire_threshold']);
        }

        /**
         * Container (ie. groups or other entity ownership)
         */
        $container = null;

        if (isset($payload['container_guid']) && $payload['container_guid']) {
            if (isset($payload['wire_threshold']) && $payload['wire_threshold']) {
                throw new UserErrorException("You can not monetize group posts");
            }

            $activity->container_guid = $payload['container_guid'];
            if ($container = $this->entitiesBuilder->single($activity->container_guid)) {
                $activity->containerObj = $container->export();
            }
            $activity->indexes = [
                "activity:container:$activity->container_guid",
                "activity:network:$activity->owner_guid"
            ];

            \Minds\Core\Events\Dispatcher::trigger('activity:container:prepare', $container->type, [
                'container' => $container,
                'activity' => $activity,
            ]);
        }

        /**
         * Tags
         */
        if (isset($payload['tags'])) {
            $activity->setTags($payload['tags']);
        }

        /**
         * License
         */
        $activity->setLicense($payload['license'] ?? $payload['attachment_license'] ?? '');

        /**
         * Attachments
         */
        if (isset($payload['attachment_guids']) && count($payload['attachment_guids']) > 0) {
            /**
             * Build out the attachment entities
             */
            $attachmentEntities = $this->entitiesBuilder->get([ 'guids' => $payload['attachment_guids'] ]) ?: [];

            $imageCount = count(array_filter($attachmentEntities, function ($attachmentEntity) {
                return $attachmentEntity instanceof Image;
            }));

            $videoCount = count(array_filter($attachmentEntities, function ($attachmentEntity) {
                return $attachmentEntity instanceof Video;
            }));

            // If neither, was this an audio upload?
            if ($imageCount === 0 && $videoCount === 0) {
                $audioService = Di::_()->get(AudioService::class);
                $attachmentEntities = [ $audioService->getByGuid($payload['attachment_guids'][0]) ];
                $audioCount = count($attachmentEntities);
            } else {
                $audioCount = 0;
            }

            // validate there is not a mix of videos and images
            if ($imageCount >= 1 && $videoCount >= 1) {
                throw new UserErrorException("You may not have both image and videos at this time");
            }

            // if videos, validate there is only 1 video
            if ($videoCount > 1) {
                throw new UserErrorException("You can only upload one video at this time");
            }

            // ensure there is a max of 4 images
            if ($imageCount > 4) {
                throw new UserErrorException("You can not upload more 4 images");
            }

            $activity->setAttachments($attachmentEntities);

            if (isset($payload['title'])) { // Only attachment posts can have titles
                $activity->setTitle($payload['title']);
            }
        }

        /**
         * Rich embeds
         */
        if ((isset($payload['link_url']) || isset($payload['url'])) && !$activity->hasAttachments()) {
            $activity
                ->setLinkTitle(rawurldecode($payload['link_title'] ?? $payload['title']))
                ->setBlurb(rawurldecode($payload['link_description'] ?? $payload['description']))
                ->setUrl(rawurldecode($payload['link_url'] ?? $payload['url']))
                ->setThumbnail($payload['link_thumbnail'] ?? $payload['thumbnail']);
        }

        /**
         * Scheduled posts
         */
        if (isset($payload['time_created'])) {
            $now = time();
            $this->entityTimeCreated->validate($activity, $payload['time_created'] ?? $now, $now);
        }

        /**
         * Site memberships
         */
        if (isset($payload['site_membership_guids']) && !empty($payload['site_membership_guids'])) {

            $siteMembershipGuids = array_map('intval', $payload['site_membership_guids']);

            // Do we have permission. If not a forbidden exception is thrown.
            $this->rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_CREATE_PAYWALL, $user);

            $this->createPaywalledEntityService->setupMemberships($activity, $siteMembershipGuids);

            if (isset($payload['title'])) {
                $activity->setTitle($payload['title']);
            }

            if (isset($payload['paywall_thumbnail'])) {
                $this->createPaywalledEntityService->processPaywallThumbnail($activity, $payload['paywall_thumbnail']);
            }
        }

        $activity->setClientMeta($request->getParsedBody()['client_meta'] ?? []);

        /**
         * Save the activity
         */
        if (isset($payload['supermind_request'])) {
            $this->manager->addSupermindRequest($payload, $activity);
        } elseif (isset($payload['supermind_reply_guid'])) {
            $this->manager->addSupermindReply($payload, $activity);
        } elseif (!$this->manager->add($activity)) {
            throw new ServerErrorException("The post could not be saved.");
        }

        if ($container) {
            \Minds\Core\Events\Dispatcher::trigger('activity:container', $container->type, [
                'container' => $container,
                'activity' => $activity,
            ]);
        }

        /**
         * Post save, update the access id and container id of the attachment entities, now we have a GUID
         */
        if (isset($attachmentEntities)) {
            // update the container guid of the image to be the activity guid
            foreach ($attachmentEntities as $attachmentEntity) {
                $attachmentEntity->container_guid = $activity->getGuid();
                $attachmentEntity->access_id = $activity->getGuid();
                $this->manager->patchAttachmentEntity($activity, $attachmentEntity);
            }
        }

        return new JsonResponse($activity->export());
    }

    /**
     * POST
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function updateExistingActivity(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        /** @var string */
        $activityGuid = $request->getAttribute('parameters')['guid'] ?? '';

        if (!$activityGuid) {
            throw new UserErrorException('You must provide a guid');
        }

        $payload = $request->getParsedBody();

        /** @var Activity */
        $activity = $this->entitiesBuilder->single($activityGuid);

        /**
         * Validate if exists
         */
        if (!$activity) {
            throw new UserErrorException('Activity not found');
        }

        // When editing media posts, they can sometimes be non-activity entities
        // so we provide some additional field
        // TODO: Anoter possible bug is the descrepency between 'description' and 'message'
        // here we are updating message field. Propose fixing this at Object/Image level
        // vs patching on activity
        // !!!!
        // !! Inheritted from v2 API - not applicable to new entities without entity_guid !!
        // !!!!
        if (!$activity instanceof Activity) {
            $subtype = $activity->getSubtype();
            $type = $activity->getType();
            $activity = $this->manager->createFromEntity($activity);
            $activity->guid = $activityGuid; // createFromEntity makes a new entity
            $activity->subtype = $subtype;
            $activity->type = $type;
        }

        /**
         * Check we can edit
         */
        if (!$activity->canEdit()) {
            throw new ForbiddenException("Invalid permission to edit this activity post");
        }

        /**
         * We edit the mutated activity so we know what has changed
         */
        $mutatedActivity = new EntityMutation($activity);

        /**
         * NSFW and Mature
         */
        $mutatedActivity->setMature(isset($payload['mature']) && !!$payload['mature']);
        $mutatedActivity->setNsfw($payload['nsfw'] ?? []);

        // If a user is mature, their post should be tagged as that too
        if ($user->isMature()) {
            $mutatedActivity->setMature(true);
        }

        /**
         * Access ID
         */
        if (isset($payload['access_id'])) {
            $mutatedActivity->setAccessId($payload['access_id']);
        }

        /**
         * Message
         */
        if (isset($payload['message'])) {
            $mutatedActivity->setMessage(rawurldecode($payload['message']));
        }

        /**
         * Time Created
         */
        if (isset($payload['time_created'])) {
            $now = time();
            try {
                // validates and sets timestamp on passed entity.
                $this->entityTimeCreated->validate(
                    entity: $mutatedActivity->getMutatedEntity(),
                    time_created: $payload['time_created'] ?? $now,
                    time_sent: $now,
                    action: $this->entityTimeCreated::UPDATE_ACTION
                );
            } catch (AlreadyPublishedException $e) {
                // soft fail.
                $this->logger->warning($e->getMessage());
            }
        }

        /**
         * Title
         */
        if (isset($payload['title']) && $activity->hasAttachments()) {
            $mutatedActivity->setTitle($payload['title']);
        }

        /**
         * Tags
         */
        if (isset($payload['tags'])) {
            $mutatedActivity->setTags($payload['tags']);
        }

        /**
         * License
         */
        $mutatedActivity->setLicense($payload['license'] ?? $payload['attachment_license'] ?? '');

        /**
         * Rich embeds
         */
        if ((isset($payload['link_url']) || isset($payload['url'])) && !$activity->hasAttachments()) {
            $mutatedActivity
                ->setLinkTitle(rawurldecode($payload['link_title'] ?? $payload['title']))
                ->setBlurb(rawurldecode($payload['link_description'] ?? $payload['description']))
                ->setUrl(rawurldecode($payload['link_url'] ?? $payload['url']))
                ->setThumbnail($payload['link_thumbnail'] ?? $payload['thumbnail']);
        }

        /**
         * Save the activity
         */
        if (!$this->manager->update($mutatedActivity)) {
            throw new ServerErrorException("The post could not be saved.");
        }

        /** @var Activity */
        $originalActivity = $mutatedActivity->getMutatedEntity();

        return new JsonResponse($originalActivity->export());
    }

    /**
     * Delete entity endpoint
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function delete(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');
        if (!($parameters['urn'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':urn not provided'
            ]);
        }

        /** @var string */
        $urn = $parameters['urn'];

        $entity = $this->manager->getByUrn($urn);

        if (!$entity) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The post does not appear to exist',
            ]);
        }

        if ($entity->canEdit()) {
            if ($this->manager->delete($entity)) {
                return new JsonResponse([
                    'status' => 'success',
                ]);
            }
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'There was an unknown error deleting this post',
        ]);
    }

    /**
     * Delete all user's reminds of entity endpoint
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function deleteRemindsOfEntityByUser(ServerRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->getAttribute('_user');

        $parameters = $request->getAttribute('parameters');

        if (!($parameters['guid'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':guid of original post not provided'
            ]);
        }

        /** @var string */
        $guid = $parameters['guid'];

        /** @var Activity */
        $activity = $this->entitiesBuilder->single($guid);

        if (!$activity) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The post does not appear to exist',
            ]);
        }

        if ($this->manager->deleteRemindsOfEntityByUser($activity, $user)) {
            return new JsonResponse([
            'status' => 'success',
            ]);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'There was an unknown error undoing this remind',
        ]);
    }

    /**
     * Get whether user has reminded this activity
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getUserHasRemindedActivity(ServerRequest $request): JsonResponse
    {

        /** @var User $user */
        $user = $request->getAttribute('_user');

        $parameters = $request->getAttribute('parameters');

        if (!($parameters['guid'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':guid of original post not provided'
            ]);
        }

        /** @var string */
        $guid = $parameters['guid'];

        /** @var Activity */
        $activity = $this->entitiesBuilder->single($guid);

        if (!$activity) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The post does not appear to exist',
            ]);
        }

        $hasReminded = $this->manager->countRemindsOfEntityByUser($activity, $user) > 0;

        return new JsonResponse([
            'status' => 'success',
            'has_reminded' => $hasReminded
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getRemindList(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getQuoteList(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([]);
    }
}
