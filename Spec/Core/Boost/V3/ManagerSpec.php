<?php

namespace Spec\Minds\Core\Boost\V3;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentCaptureFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentRefundFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\EntityTypeNotAllowedInLocationException;
use Minds\Core\Boost\V3\Exceptions\IncorrectBoostStatusException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
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

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $paymentProcessor;
    private Collaborator $entitiesBuilder;

    public function let(
        Repository $repository,
        PaymentProcessor $paymentProcessor,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->repository = $repository;
        $this->paymentProcessor = $paymentProcessor;
        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith($this->repository, $this->paymentProcessor, $this->entitiesBuilder);
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

        $this->entitiesBuilder->single(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->paymentProcessor->setupBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->createBoost(Argument::type(Boost::class))
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

    public function it_should_approve_boost(
        Boost $boost
    ): void {
        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();
        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();

        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($boost);

        $this->repository->approveBoost(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->captureBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->approveBoost('123')
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

        $this->shouldThrow(BoostPaymentCaptureFailedException::class)->during('approveBoost', ['123']);
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

        $this->repository->approveBoost(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->paymentProcessor->captureBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->shouldThrow(ServerErrorException::class)->during('approveBoost', ['123']);
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

        $this->shouldThrow(BoostNotFoundException::class)->during('approveBoost', ['123']);
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

        $this->repository->rejectBoost(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->rejectBoost('123')
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

        $this->shouldThrow(IncorrectBoostStatusException::class)->during('rejectBoost', ['123']);
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

        $this->shouldThrow(BoostPaymentRefundFailedException::class)->during('rejectBoost', ['123']);
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

        $this->repository->rejectBoost(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->paymentProcessor->refundBoostPayment(Argument::type(Boost::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->shouldThrow(ServerErrorException::class)->during('rejectBoost', ['123']);
    }

    /**
     * @return void
     */
    public function it_should_try_reject_boost_and_throw_boost_not_found_exception(): void
    {
        $this->repository->getBoostByGuid(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(BoostNotFoundException::class);

        $this->shouldThrow(BoostNotFoundException::class)->during('rejectBoost', ['123']);
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
            Argument::type('integer'),
            Argument::type('bool')
        )
            ->shouldBeCalledOnce()
            ->willYield([$boost]);

        $this->getBoosts()
            ->shouldReturnAnInstanceOf(Response::class);
    }
}
