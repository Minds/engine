<?php

namespace Spec\Minds\Core\Boost\V3;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentCaptureFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentRefundFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\EntityTypeNotAllowedInLocationException;
use Minds\Core\Boost\V3\Exceptions\IncorrectBoostStatusException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Analytics\Views\Manager as ViewsManager;
use Minds\Core\Security\ACL;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Boost\V3\Manager;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\PaymentProcessor;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Stripe\Exception\ApiErrorException;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $paymentProcessor;
    private Collaborator $entitiesBuilder;
    private Collaborator $actionEventDelegate;
    private Collaborator $preApprovalManager;
    private Collaborator $viewsManager;
    private Collaborator $acl;

    public function let(
        Repository $repository,
        PaymentProcessor $paymentProcessor,
        EntitiesBuilder $entitiesBuilder,
        ActionEventDelegate $actionEventDelegate,
        PreApprovalManager $preApprovalManager,
        ViewsManager $viewsManager,
        ACL $acl
    ) {
        $this->repository = $repository;
        $this->paymentProcessor = $paymentProcessor;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->actionEventDelegate = $actionEventDelegate;
        $this->preApprovalManager = $preApprovalManager;
        $this->viewsManager = $viewsManager;
        $this->acl = $acl;

        $this->beConstructedWith(
            $this->repository,
            $this->paymentProcessor,
            $this->entitiesBuilder,
            $this->actionEventDelegate,
            $this->preApprovalManager,
            $this->viewsManager,
            $this->acl
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
            ->shouldBeCalledOnce()
            ->willReturn('activity');

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class))
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
            'payment_method' => 1,
            'daily_bid' => 10,
            'duration_days' => 2
        ];

        $this->createBoost($boostData)
            ->shouldBeEqualTo(true);
    }

    public function it_should_create_boost_when_pre_approved(
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
            ->shouldBeCalledOnce()
            ->willReturn('activity');

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class))
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
            ->shouldBeCalledOnce()
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

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledOnce()
            ->willReturn('activity');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);
        
        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class))
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
    public function it_should_try_to_create_boost_and_throw_server_error_exception(
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
            ->shouldBeCalledOnce()
            ->willReturn('activity');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class))
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

        $this->shouldThrow(ServerErrorException::class)->during('createBoost', [$boostData]);
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

        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $entity->getType()
            ->shouldBeCalledOnce()
            ->willReturn('activity');

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class))
            ->shouldNotBeCalled()
            ->willReturn(true);

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
    public function it_should_try_to_approve_boost_and_throw_payment_capture_failed_exception(
        Boost $boost
    ): void {
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
    public function it_should_cancel_boost(
        User $user,
        Boost $boost
    ): void {
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

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

        $this->repository->cancelBoost(Argument::type('string'), '123')
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->cancelBoost('123')
            ->shouldBeEqualTo(true);
    }

    /**
     * @param Boost $boost
     * @return void
     */
    public function it_should_try_cancel_boost_and_throw_incorrect_status_exception(
        Boost $boost
    ): void {
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
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

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
            ->willReturn(true);

        $this->acl->read($boost2)
            ->shouldBeCalled()
            ->willReturn(false);

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
            ->shouldBeLike(new Response([$boost1], false));
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

    // public function it_should_get_boosts_as_feed_sync_entity(
    //     Boost $boost
    // ): void {
    //     $boost = (new Boost(
    //         '123',
    //         1,
    //         1,
    //         1,
    //         1,
    //         1,
    //         1,
    //         1,
    //         1,
    //         1,
    //         '123',
    //         1,
    //         1
    //     ))->setOwnerGuid('123')
    //         ->setGuid('234');

    //     $this->repository->getBoosts(
    //         limit: Argument::type('integer'),
    //         offset: Argument::type('integer'),
    //         targetStatus: null,
    //         forApprovalQueue: Argument::type('bool'),
    //         targetUserGuid: null,
    //         orderByRanking: Argument::type('bool'),
    //         targetAudience: Argument::type('integer'),
    //         targetLocation: null,
    //         paymentMethod: null,
    //         loggedInUser: null,
    //         hasNext: Argument::type('bool'),
    //     )
    //         ->shouldBeCalledOnce()
    //         ->willYield([$boost]);

    //     $this->getBoostFeed()
    //         ->shouldReturnAnInstanceOf(Response::class);
    // }
}
