<?php

namespace Spec\Minds\Core\Boost\V3;

use Minds\Common\Repository\Response;
use Minds\Core\Analytics\Views\Manager as ViewsManager;
use Minds\Core\Blogs\Blog;
use Minds\Core\Boost\V3\Cache\BoostFeedCache;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Exceptions\BoostCreationFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentCaptureFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentRefundFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\EntityTypeNotAllowedInLocationException;
use Minds\Core\Boost\V3\Exceptions\IncorrectBoostStatusException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Manager;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Models\BoostEntityWrapper;
use Minds\Core\Boost\V3\PaymentProcessor;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Config\Config;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Entities\GuidLinkResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Payments\InAppPurchases\Manager as InAppPurchasesManager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Settings\Models\BoostPartnerSuitability;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\TenantFactoryMockBuilder;
use Stripe\Exception\ApiErrorException;

class ManagerSpec extends ObjectBehavior
{
    use TenantFactoryMockBuilder;
    
    private Collaborator $repository;
    private Collaborator $paymentProcessor;
    private Collaborator $entitiesBuilder;
    private Collaborator $actionEventDelegate;
    private Collaborator $preApprovalManager;
    private Collaborator $viewsManager;
    private Collaborator $acl;
    private Collaborator $guidLinkResolver;
    private Collaborator $userSettingsManager;
    private Collaborator $experimentsManager;
    private Collaborator $inAppPurchasesManagerMock;
    private Collaborator $configMock;
    private Collaborator $tenantBootServiceMock;
    private Collaborator $boostFeedCacheMock;

    public function let(
        Repository            $repository,
        PaymentProcessor      $paymentProcessor,
        EntitiesBuilder       $entitiesBuilder,
        ActionEventDelegate   $actionEventDelegate,
        PreApprovalManager    $preApprovalManager,
        ViewsManager          $viewsManager,
        ACL                   $acl,
        GuidLinkResolver      $guidLinkResolver,
        UserSettingsManager   $userSettingsManager,
        ExperimentsManager    $experimentsManager,
        InAppPurchasesManager $inAppPurchasesManager,
        Config                $config,
        MultiTenantBootService $tenantBootServiceMock,
        BoostFeedCache        $boostFeedCacheMock
    ) {
        $this->repository = $repository;
        $this->paymentProcessor = $paymentProcessor;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->actionEventDelegate = $actionEventDelegate;
        $this->preApprovalManager = $preApprovalManager;
        $this->viewsManager = $viewsManager;
        $this->acl = $acl;
        $this->guidLinkResolver = $guidLinkResolver;
        $this->userSettingsManager = $userSettingsManager;
        $this->experimentsManager = $experimentsManager;
        $this->inAppPurchasesManagerMock = $inAppPurchasesManager;
        $this->configMock = $config;
        $this->tenantBootServiceMock = $tenantBootServiceMock;
        $this->boostFeedCacheMock = $boostFeedCacheMock;

        $this->beConstructedWith(
            $this->repository,
            $this->paymentProcessor,
            $this->entitiesBuilder,
            $this->actionEventDelegate,
            $this->preApprovalManager,
            $this->viewsManager,
            $this->acl,
            $this->guidLinkResolver,
            $this->userSettingsManager,
            $this->experimentsManager,
            $this->inAppPurchasesManagerMock,
            $this->configMock,
            $this->tenantBootServiceMock,
            $this->boostFeedCacheMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Manager::class);
    }

    /**
     * @param User $user
     * @param Entity $entity
     * @return void
     * @throws BoostPaymentSetupFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function it_should_create_boost(
        User $user,
        Entity $entity
    ): void {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('activity');

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();
        $this->paymentProcessor->commitTransaction()
            ->shouldBeCalledOnce();

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::that(function ($arg) {
            return $arg->getStatus() === null;
        }))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'target_platform_web' => true,
            'target_platform_android' => true,
            'target_platform_ios' => true,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2,
            'goal' => 1,
            'goal_button_text' => null,
            'goal_button_url' => null
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    public function it_should_create_boost_for_linked_activity_for_a_blog(
        User $user,
        Blog $blog,
        Activity $activity
    ): void {
        $entityGuid = '123';
        $activityGuid = '234';

        $blog->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('object');

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalledOnce()
            ->willReturn($blog);

        $this->guidLinkResolver->resolveActivityFromEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();
        $this->paymentProcessor->commitTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::that(function ($arg) {
            return $arg->getStatus() === null;
        }))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'entity_guid' => $entityGuid,
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    public function it_should_create_boost_for_linked_activity_for_an_image(
        User $user,
        Image $image,
        Activity $activity
    ): void {
        $entityGuid = '123';
        $activityGuid = '234';

        $image->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('object');

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalledOnce()
            ->willReturn($image);

        $this->guidLinkResolver->resolveActivityFromEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();


        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();
        $this->paymentProcessor->commitTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::that(function ($arg) {
            return $arg->getStatus() === null;
        }))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'entity_guid' => $entityGuid,
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    public function it_should_create_boost_for_linked_activity_for_a_video(
        User $user,
        Video $video,
        Activity $activity
    ): void {
        $entityGuid = '123';
        $activityGuid = '234';

        $video->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('object');

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalledOnce()
            ->willReturn($video);

        $this->guidLinkResolver->resolveActivityFromEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();
        $this->paymentProcessor->commitTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::that(function ($arg) {
            return $arg->getStatus() === null;
        }))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'entity_guid' => $entityGuid,
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    public function it_should_NOT_create_boost_for_a_blog_when_there_is_no_linked_activity(
        Blog $blog,
    ): void {
        $entityGuid = '123';

        $blog->getType()
            ->shouldBeCalledTimes(3)
            ->willReturn('object');

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalledOnce()
            ->willReturn($blog);

        $this->guidLinkResolver->resolveActivityFromEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->createBoost(Argument::any())
            ->shouldNotBeCalled();

        $boostData = [
            'entity_guid' => $entityGuid,
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->shouldThrow(EntityTypeNotAllowedInLocationException::class)
            ->during('createBoost', [$boostData]);
    }

    public function it_should_NOT_create_boost_for_a_image_when_there_is_no_linked_activity(
        Image $image,
    ): void {
        $entityGuid = '123';

        $image->getType()
            ->shouldBeCalledTimes(3)
            ->willReturn('object');

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalledOnce()
            ->willReturn($image);

        $this->guidLinkResolver->resolveActivityFromEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->createBoost(Argument::any())
            ->shouldNotBeCalled();

        $boostData = [
            'entity_guid' => $entityGuid,
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->shouldThrow(EntityTypeNotAllowedInLocationException::class)
            ->during('createBoost', [$boostData]);
    }

    public function it_should_NOT_create_boost_for_a_video_when_there_is_no_linked_activity(
        Video $video,
    ): void {
        $entityGuid = '123';

        $video->getType()
            ->shouldBeCalledTimes(3)
            ->willReturn('object');

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalledOnce()
            ->willReturn($video);

        $this->guidLinkResolver->resolveActivityFromEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->createBoost(Argument::any())
            ->shouldNotBeCalled();

        $boostData = [
            'entity_guid' => $entityGuid,
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->shouldThrow(EntityTypeNotAllowedInLocationException::class)
            ->during('createBoost', [$boostData]);
    }

    public function it_should_create_boost_when_pre_approved(
        User $user,
        Entity $entity
    ): void {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);


        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('activity');

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();
        $this->paymentProcessor->commitTransaction()
            ->shouldBeCalledOnce();

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::that(function ($arg) {
            return $arg->getStatus() === BoostStatus::APPROVED;
        }))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->captureBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $boostData = [
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    /**
     * @param Entity $entity
     * @return void
     */
    public function it_should_try_to_create_boost_and_throw_incorrect_entity_type_location_combo_exception(
        Entity $entity
    ): void {
        $entity->getType()
            ->shouldBeCalledTimes(3)
            ->willReturn('user');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $boostData = [
            'entity_guid' => '123',
            'target_location' => 1
        ];

        $this->shouldThrow(EntityTypeNotAllowedInLocationException::class)->during('createBoost', [$boostData]);
    }

    /**
     * @param User $user
     * @param Entity $entity
     * @return void
     */
    public function it_should_try_to_create_boost_and_throw_payment_setup_failed_exception(
        User $user,
        Entity $entity
    ): void {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);


        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('activity');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->shouldThrow(BoostPaymentSetupFailedException::class)->during('createBoost', [$boostData]);
    }

    /**
     * @param User $user
     * @param Entity $entity
     * @return void
     */
    public function it_should_try_to_create_boost_and_throw_boost_creation_failed_exception(
        User $user,
        Entity $entity
    ): void {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);


        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('activity');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->shouldThrow(BoostCreationFailedException::class)->during('createBoost', [$boostData]);
    }

    /**
     * @param User $user
     * @param Entity $entity
     * @return void
     * @throws BoostPaymentSetupFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function it_should_create_an_onchain_boost_with_a_supplied_guid(
        User $user,
        Entity $entity
    ): void {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->experimentsManager->isOn('minds-4030-boost-platform-targeting')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledTimes(2)
            ->willReturn('activity');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->beginTransaction()
            ->shouldBeCalledOnce();
        $this->paymentProcessor->commitTransaction()
            ->shouldBeCalledOnce();

        $this->paymentProcessor->createMindsPayment(Argument::type(Boost::class), $user, null)
            ->shouldBeCalledOnce()
            ->willReturn(new PaymentDetails());

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class), $user, Argument::type(PaymentDetails::class))
            ->shouldNotBeCalled();

        $this->repository->createBoost(Argument::that(function ($arg) {
            return $arg->getStatus() === BoostStatus::PENDING_ONCHAIN_CONFIRMATION &&
                $arg->getPaymentTxId() === '0x123' &&
                $arg->getGuid() === '567';
        }))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $boostData = [
            'guid' => '567',
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 3,
            'payment_tx_id' => '0x123',
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    public function it_should_approve_boost(
        Boost $boost
    ): void {
        $adminGuid = '345';

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->approveBoost(Argument::type('string'), $adminGuid)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->captureBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->actionEventDelegate->onApprove($boost)
            ->shouldBeCalled();

        $this->approveBoost('123', $adminGuid)
            ->shouldBeEqualTo(true);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_approve_boost_and_throw_incorrect_status_exception(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::REFUND_IN_PROGRESS);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->rollbackTransaction()
            ->shouldBeCalled();

        $this->shouldThrow(IncorrectBoostStatusException::class)->during('approveBoost', ['123', '234']);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_to_approve_boost_and_throw_payment_capture_failed_exception(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();
        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->paymentProcessor->captureBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->shouldThrow(BoostPaymentCaptureFailedException::class)->during('approveBoost', ['123', '234']);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_to_approve_boost_and_throw_server_error_exception(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();
        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->approveBoost(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->paymentProcessor->captureBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->shouldThrow(ServerErrorException::class)->during('approveBoost', ['123', '234']);
    }

    /**
     * @return void
     */
    public function it_should_try_approve_boost_and_throw_boost_not_found_exception(): void
    {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();
        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(BoostNotFoundException::class);

        $this->shouldThrow(BoostNotFoundException::class)->during('approveBoost', ['123', '234']);
    }

    /**
     * @param Boost $boost
     * @return void
     * @throws BoostNotFoundException
     * @throws BoostPaymentCaptureFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function it_should_reject_boost(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_PROCESSED
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->rejectBoost(Argument::type('string'), Argument::type("integer"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->actionEventDelegate->onReject($boost, BoostRejectionReason::WRONG_AUDIENCE)
            ->shouldBeCalled();

        $this->rejectBoost('123', BoostRejectionReason::WRONG_AUDIENCE)
            ->shouldBeEqualTo(true);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_reject_boost_and_throw_incorrect_status_exception(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::REFUND_IN_PROGRESS);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->shouldThrow(IncorrectBoostStatusException::class)->during('rejectBoost', ['123', BoostRejectionReason::WRONG_AUDIENCE]);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_reject_boost_and_throw_payment_refund_failed_exception(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->shouldThrow(BoostPaymentRefundFailedException::class)->during('rejectBoost', ['123', BoostRejectionReason::WRONG_AUDIENCE]);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_reject_boost_and_throw_server_error_exception(
        Boost $boost
    ): void {
        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_PROCESSED
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->rejectBoost(Argument::type('string'), Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->shouldThrow(ServerErrorException::class)->during('rejectBoost', ['123', BoostRejectionReason::WRONG_AUDIENCE]);
    }

    /**
     * @return void
     */
    public function it_should_try_reject_boost_and_throw_boost_not_found_exception(): void
    {
        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(BoostNotFoundException::class);

        $this->shouldThrow(BoostNotFoundException::class)->during('rejectBoost', ['123', BoostRejectionReason::WRONG_AUDIENCE]);
    }

    /**
     * @param User $user
     * @param Boost $boost
     * @return void
     * @throws BoostNotFoundException
     * @throws BoostPaymentRefundFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws ApiErrorException
     */
    public function it_should_cancel_and_refund_pending_boosts(
        User $user,
        Boost $boost
    ): void {
        $boostOwnerGuid = 1234567890123456;
        $userGuid = $boostOwnerGuid;

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn(BoostStatus::PENDING);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn($boostOwnerGuid);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_PROCESSED
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->cancelBoost(Argument::type('string'), $userGuid)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->cancelBoost('123')
            ->shouldBeEqualTo(true);
    }

    public function it_should_cancel_and_not_refund_non_pending_boosts(
        User $user,
        Boost $boost
    ): void {
        $boostOwnerGuid = 1234567890123456;
        $userGuid = $boostOwnerGuid;

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn(BoostStatus::APPROVED);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn($boostOwnerGuid);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->cancelBoost(Argument::type('string'), $userGuid)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldNotBeCalled();

        $this->cancelBoost('123')
            ->shouldBeEqualTo(true);
    }

    public function it_should_not_cancel_a_boost_when_not_the_boost_owner(
        User $user,
        Boost $boost
    ): void {
        $boostOwnerGuid = 1234567890123456;
        $userGuid = 2234567890123456;

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->isAdmin()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn($boostOwnerGuid);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->cancelBoost(Argument::type('string'), $userGuid)
            ->shouldNotBeCalled();

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringCancelBoost('123');
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_cancel_boost_and_throw_incorrect_status_exception(
        Boost $boost,
        User $user
    ): void {
        $boostOwnerGuid = 1234567890123456;
        $userGuid = $boostOwnerGuid;

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn($boostOwnerGuid);

        $boost->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(BoostStatus::REFUND_IN_PROGRESS);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->shouldThrow(IncorrectBoostStatusException::class)->during('cancelBoost', ['123']);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_cancel_boost_and_throw_payment_refund_failed_exception(
        Boost $boost,
        User $user
    ): void {
        $boostOwnerGuid = 1234567890123456;
        $userGuid = $boostOwnerGuid;

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn($boostOwnerGuid);

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->shouldThrow(BoostPaymentRefundFailedException::class)->during('cancelBoost', ['123']);
    }

    /**
     * @param User $user
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_cancel_boost_and_throw_server_error_exception(
        User $user,
        Boost $boost
    ): void {
        $boostOwnerGuid = 1234567890123456;
        $userGuid = $boostOwnerGuid;

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn($boostOwnerGuid);

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn(BoostStatus::PENDING);

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->updateStatus(
            Argument::type('string'),
            BoostStatus::REFUND_PROCESSED
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->cancelBoost(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->shouldThrow(ServerErrorException::class)->during('cancelBoost', ['123']);
    }

    /**
     * @return void
     */
    public function it_should_try_cancel_boost_and_throw_boost_not_found_exception(): void
    {
        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(BoostNotFoundException::class);

        $this->shouldThrow(BoostNotFoundException::class)->during('cancelBoost', ['123']);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_get_boosts(
        Boost $boost
    ): void {
        $hasNext = false;

        $this->repository->getBoosts(
            Argument::type('integer'),
            Argument::type('integer'),
            null,
            Argument::type('bool'),
            null,
            Argument::type('bool'),
            null,
            null,
            null,
            null,
            null,
            Argument::type('bool')
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->getBoosts()
            ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_boosts_without_acl_filtered_boost(
        Boost $boost1,
        Boost $boost2
    ): void {
        $hasNext = false;

        $this->acl->read($boost1)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->acl->read($boost2)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->getBoosts(
            Argument::type('integer'),
            Argument::type('integer'),
            null,
            Argument::type('bool'),
            null,
            Argument::type('bool'),
            null,
            null,
            null,
            null,
            null,
            Argument::type('bool')
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost1, $boost2]);

        $this->getBoosts()
            ->shouldBeLike(new Response([$boost2], false));
    }

    public function it_should_get_admin_stats()
    {
        $globalPendingStats = [
            'safe_count' => 56,
            'controversial_count' => 92
        ];

        $this->repository->getAdminStats(
            targetStatus: BoostStatus::PENDING
        )
            ->shouldBeCalled()
            ->willReturn($globalPendingStats);

        $this->getAdminStats()->shouldBeLike(new Response([
            'global_pending' => [
                'safe_count' => $globalPendingStats['safe_count'],
                'controversial_count' => $globalPendingStats['controversial_count']
            ]
        ]));
    }

    public function it_should_get_boost_by_guid(Boost $boost)
    {
        $boostGuid = '123';

        $this->repository->getBoostByGuid($boostGuid)
            ->shouldBeCalled()
            ->willReturn($boost);

        $this->acl->read($boost)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->getBoostByGuid($boostGuid)->shouldBe($boost);
    }

    public function it_should_return_null_when_no_boost_is_found_when_getting_boost_by_guid()
    {
        $boostGuid = '123';

        $this->repository->getBoostByGuid($boostGuid)
            ->shouldBeCalled()
            ->willThrow(new BoostNotFoundException());

        $this->acl->read(Argument::any())
            ->shouldNotBeCalled();

        $this->getBoostByGuid($boostGuid)->shouldBe(null);
    }

    public function it_should_return_null_when_acl_read_is_failed_when_getting_boost_by_guid(Boost $boost)
    {
        $boostGuid = '123';

        $this->repository->getBoostByGuid($boostGuid)
            ->shouldBeCalled()
            ->willReturn($boost);

        $this->acl->read($boost)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->getBoostByGuid($boostGuid)->shouldBe(null);
    }

    public function it_should_get_boosts_as_feed_sync_entity(
        Boost $boost,
        User $user
    ): void {
        $boostGuid = '234';
        $ownerGuid = '123';
        $createdTimestamp = 999999;
        $boostUrn = "urn:boost:$boostGuid";
        $activity = new Activity();

        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid($ownerGuid)
            ->setGuid($boostGuid)
            ->setCreatedTimestamp($createdTimestamp)
            ->setEntity($activity);

        $this->setUser($user);

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->getBoosts(
            limit: 12,
            offset: 0,
            targetStatus: null,
            forApprovalQueue: false,
            targetUserGuid: null,
            orderByRanking: false,
            targetAudience: BoostTargetAudiences::SAFE,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: $user,
            hasNext: false,
        )
            ->shouldBeCalled()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->acl->read(Argument::type(Boost::class))
            ->willReturn(true);


        $this->getBoostFeed()->toArray()->shouldBeLike([
            (new FeedSyncEntity())
                ->setGuid($boostGuid)
                ->setOwnerGuid($ownerGuid)
                ->setTimestamp($createdTimestamp)
                ->setUrn($boostUrn)
                ->setEntity(new BoostEntityWrapper($boost))
        ]);
    }

    public function it_should_get_boosts_as_feed_sync_entity_from_cache(
        Boost $boost,
        User $user
    ): void {
        $boostGuid = '234';
        $ownerGuid = '123';
        $createdTimestamp = 999999;
        $boostUrn = "urn:boost:$boostGuid";
        $activity = new Activity();

        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid($ownerGuid)
            ->setGuid($boostGuid)
            ->setCreatedTimestamp($createdTimestamp)
            ->setEntity($activity);

        $this->setUser($user);

        $this->boostFeedCacheMock->get(
            limit: 12,
            offset: 0,
            targetStatus: null,
            forApprovalQueue: false,
            targetUserGuid: null,
            orderByRanking: false,
            targetAudience: BoostTargetAudiences::SAFE,
            targetLocation: null,
            loggedInUserGuid: null,
            hasNext: false
        )
            ->shouldBeCalled()
            ->willReturn([$boost]);

        $this->repository->getBoosts(Argument::cetera())
            ->shouldNotBeCalled();

        $this->acl->read(Argument::type(Boost::class))
            ->willReturn(true);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldNotBeCalled();

        $this->getBoostFeed()->toArray()->shouldBeLike([
            (new FeedSyncEntity())
                ->setGuid($boostGuid)
                ->setOwnerGuid($ownerGuid)
                ->setTimestamp($createdTimestamp)
                ->setUrn($boostUrn)
                ->setEntity(new BoostEntityWrapper($boost))
        ]);
    }

    public function it_should_get_boosts_when_boost_serving_user_allows_only_safe_boosts_and_target_audience_is_safe(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::SAFE,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(BoostPartnerSuitability::SAFE);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::SAFE)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_boosts_when_boost_serving_user_allows_only_safe_boosts_and_target_audience_is_controversial(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::SAFE,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(BoostPartnerSuitability::SAFE);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::CONTROVERSIAL)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_boosts_when_boost_serving_user_allows_controversial_boosts_and_target_audience_is_safe(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);
            
        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::SAFE,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(BoostPartnerSuitability::CONTROVERSIAL);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::SAFE)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_boosts_when_boost_serving_user_allows_controversial_boosts_and_target_audience_is_controversial(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);
            
        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::CONTROVERSIAL,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(BoostPartnerSuitability::CONTROVERSIAL);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::CONTROVERSIAL)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_boosts_when_boost_serving_user_has_no_boost_partner_settings_and_target_audience_is_safe(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::SAFE,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::SAFE)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_boosts_when_boost_serving_user_has_no_boost_partner_settings_and_target_audience_is_controversial(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::CONTROVERSIAL,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::CONTROVERSIAL)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_no_boosts_when_serving_user_has_boost_partner_settings_disabled(
        Boost $boost,
        User $user,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: Argument::any(),
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldNotBeCalled()
            ->willYield([$boost]);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $userSettings->getBoostPartnerSuitability()
            ->shouldBeCalled()
            ->willReturn(BoostPartnerSuitability::DISABLED);

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldBeCalled()
            ->willReturn($userSettings);

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::CONTROVERSIAL)
                ->shouldBeLike(new Response([]));
    }

    public function it_should_get_boosts_when_no_serving_user_is_found_and_target_audience_is_controversial(
        Boost $boost,
        UserSettings $userSettings
    ): void {
        $servedByGuid = '654';
        $boost = (new Boost(
            '123',
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))->setOwnerGuid('123')
            ->setGuid('234');

        $this->boostFeedCacheMock->get(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->getBoosts(
            limit: Argument::type('integer'),
            offset: Argument::type('integer'),
            targetStatus: null,
            forApprovalQueue: Argument::type('bool'),
            targetUserGuid: null,
            orderByRanking: Argument::type('bool'),
            targetAudience: BoostTargetAudiences::CONTROVERSIAL,
            targetLocation: null,
            entityGuid: null,
            paymentMethod: null,
            loggedInUser: null,
            hasNext: Argument::type('bool'),
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->boostFeedCacheMock->set(
            Argument::cetera()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($servedByGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->userSettingsManager->setUser(Argument::any())
            ->shouldNotBeCalled();

        $userSettings->getBoostPartnerSuitability()
            ->shouldNotBeCalled();

        $this->userSettingsManager->getUserSettings(allowEmpty: true)
            ->shouldNotBeCalled();

        $this->getBoostFeed(servedByGuid: $servedByGuid, targetAudience: BoostTargetAudiences::CONTROVERSIAL)
                ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_force_reject_by_entity_guid_with_default_statuses()
    {
        $entityGuid = '123';
        $reason = BoostRejectionReason::REPORT_UPHELD;
        $statuses = [ BoostStatus::APPROVED, BoostStatus::PENDING ];

        $this->repository->forceRejectByEntityGuid(
            entityGuid: $entityGuid,
            reason: $reason,
            statuses: $statuses
        )->shouldBeCalled()
            ->willReturn(true);

        $this->forceRejectByEntityGuid(
            entityGuid: $entityGuid,
            reason: $reason
        )->shouldBe(true);
    }

    public function it_should_force_reject_by_entity_guid_with_custom_statuses()
    {
        $entityGuid = '123';
        $reason = BoostRejectionReason::REPORT_UPHELD;
        $statuses = [ BoostStatus::REFUND_IN_PROGRESS ];

        $this->repository->forceRejectByEntityGuid(
            entityGuid: $entityGuid,
            reason: $reason,
            statuses: $statuses
        )->shouldBeCalled()
            ->willReturn(true);

        $this->forceRejectByEntityGuid(
            entityGuid: $entityGuid,
            reason: $reason,
            statuses: $statuses
        )->shouldBe(true);
    }

    // cancelByEntityGuid

    public function it_should_cancel_by_entity_guid(): void
    {
        $entityGuid = '123';
        $statuses = [ BoostStatus::APPROVED, BoostStatus::PENDING ];
        $boost1 = $this->generateBoost('123');
        $boost2 = $this->generateBoost('234');

        $this->repository->getBoosts(
            Argument::any(),
            Argument::any(),
            BoostStatus::APPROVED,
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            $entityGuid,
            Argument::any(),
            Argument::any(),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([$boost1, $boost2]);

        $this->repository->cancelByEntityGuid(
            entityGuid: $entityGuid,
            statuses: $statuses
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->actionEventDelegate->onCancel($boost1)
            ->shouldBeCalled();

        $this->actionEventDelegate->onCancel($boost2)
            ->shouldBeCalled();

        $this->cancelByEntityGuid(
            entityGuid: $entityGuid,
            statuses: $statuses
        )->shouldBe(true);
    }

    public function it_should_not_send_emails_on_failure_when_cancelling_by_entity_guid(): void
    {
        $entityGuid = '123';
        $statuses = [ BoostStatus::APPROVED, BoostStatus::PENDING ];
        $boost1 = $this->generateBoost('123');
        $boost2 = $this->generateBoost('234');

        $this->repository->getBoosts(
            Argument::any(),
            Argument::any(),
            BoostStatus::APPROVED,
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            $entityGuid,
            Argument::any(),
            Argument::any(),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([$boost1, $boost2]);

        $this->repository->cancelByEntityGuid(
            entityGuid: $entityGuid,
            statuses: $statuses
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $this->actionEventDelegate->onCancel($boost1)
            ->shouldNotBeCalled();

        $this->actionEventDelegate->onCancel($boost2)
            ->shouldNotBeCalled();

        $this->cancelByEntityGuid(
            entityGuid: $entityGuid,
            statuses: $statuses
        )->shouldBe(true);
    }

    // shouldShowBoosts

    public function it_should_determine_if_a_user_should_be_shown_boosts(
        User $targetUser
    ): void {
        $targetUser->get('disabled_boost')
            ->shouldBeCalled()
            ->willReturn(false);

        $targetUser->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(time() - 604801);

        $this->callOnWrappedObject('shouldShowBoosts', [$targetUser])
            ->shouldBe(true);
    }

    public function it_should_determine_if_a_user_should_be_shown_boosts_because_they_previously_disabled_boosts_but_are_NOT_plus(
        User $targetUser
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);
        
        $targetUser->get('disabled_boost')
            ->shouldBeCalled()
            ->willReturn(true);

        $targetUser->isPlus()
            ->shouldBeCalled()
            ->willReturn(false);

        $targetUser->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(time() - 604801);

        $this->callOnWrappedObject('shouldShowBoosts', [$targetUser])
            ->shouldBe(true);
    }

    public function it_should_determine_if_a_user_should_NOT_be_shown_boosts_because_they_are_plus_and_disabled_boosts(
        User $targetUser
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $targetUser->get('disabled_boost')
            ->shouldBeCalled()
            ->willReturn(true);

        $targetUser->isPlus()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShowBoosts', [$targetUser])
            ->shouldBe(false);
    }

    public function it_should_determine_if_a_user_should_NOT_be_shown_boosts_because_they_are_new(
        User $targetUser
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $targetUser->get('disabled_boost')
            ->shouldBeCalled()
            ->willReturn(false);

        $targetUser->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(time() - 1000);

        $this->callOnWrappedObject('shouldShowBoosts', [$targetUser])
            ->shouldBe(false);
    }

    public function it_should_determine_if_a_user_should_be_shown_boosts_because_they_are_on_a_tenant_domain_with_boost_enabled(
        User $targetUser
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(123);

        $this->configMock->get('tenant')
            ->shouldBeCalled()
            ->willReturn($this->generateTenantMock(
                config: $this->generateTenantConfigMock(
                    boostEnabled: true
                )
            ));

        $this->callOnWrappedObject('shouldShowBoosts', [$targetUser])
            ->shouldBe(true);
    }

    public function it_should_determine_if_a_user_should_NOT_be_shown_boosts_because_they_are_on_a_tenant_domain_with_boost_disabled(
        User $targetUser
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(123);

        $this->configMock->get('tenant')
            ->shouldBeCalled()
            ->willReturn($this->generateTenantMock(
                config: $this->generateTenantConfigMock(
                    boostEnabled: false
                )
            ));

        $this->callOnWrappedObject('shouldShowBoosts', [$targetUser])
            ->shouldBe(false);
    }


    private function generateBoost(string $guid = '123')
    {
        $boost = (new Boost(
            $guid,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            '123',
            1,
            1
        ))
            ->setOwnerGuid('123')
            ->setGuid('234');

        return $boost;
    }
}
