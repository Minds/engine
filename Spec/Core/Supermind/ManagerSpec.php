<?php

namespace Spec\Minds\Core\Supermind;

use ArrayIterator;
use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Supermind\Delegates\EventsDelegate;
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
use Minds\Core\Supermind\Exceptions\SupermindUnauthorizedSenderException;
use Minds\Core\Supermind\Manager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Core\Supermind\Repository;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\UserNotFoundException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;

class ManagerSpec extends ObjectBehavior
{
    use CommonMatchers;

    /** @var Repository */
    private Collaborator $repository;

    /** @var SupermindPaymentProcessor */
    private Collaborator $paymentProcessor;

    /** @var EventsDelegate */
    private Collaborator $eventsDelegate;

    /** @var ACL */
    private Collaborator $acl;

    /** @var EntitiesBuilder */
    private Collaborator $entitiesBuilder;

    /** @var Logger */
    private Collaborator $logger;

    public function let(
        Repository $repository,
        SupermindPaymentProcessor $paymentProcessor,
        EventsDelegate $eventsDelegate,
        ACL $acl,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $repository,
            $paymentProcessor,
            $eventsDelegate,
            $acl,
            $entitiesBuilder,
            null,
            $logger
        );

        $this->repository = $repository;
        $this->paymentProcessor = $paymentProcessor;
        $this->eventsDelegate = $eventsDelegate;
        $this->acl = $acl;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_set_user(User $user)
    {
        $this->paymentProcessor->setUser($user)
            ->shouldBeCalled();
        $this->setUser($user);
    }

    // addSupermindRequest

    public function it_should_add_a_supermind_request_for_cash(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';
        $paymentIntentId = 'pay_123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willReturn($paymentIntentId);

        $supermindRequest->setPaymentTxID($paymentIntentId)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalled();

        $this->addSupermindRequest($supermindRequest, $paymentMethodId)->shouldBe(true);
    }

    public function it_should_throw_forbidden_exception_when_adding_a_supermind_request_if_acl_fails(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(ForbiddenException::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    public function it_should_cancel_a_supermind_request_for_cash_if_adding_request_fails(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';
        $paymentIntentId = 'pay_123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentIntentId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willReturn($paymentIntentId);

        $supermindRequest->setPaymentTxID($paymentIntentId)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->cancelPaymentIntent($paymentIntentId)
            ->shouldBeCalled();

        $this->repository->rollbackTransaction()
            ->shouldBeCalled();

        $this->repository->commitTransaction()
            ->shouldBeCalled();

        $this->addSupermindRequest($supermindRequest, $paymentMethodId)->shouldBe(true);
    }

    public function it_should_rollback_a_supermind_request_for_cash_if_payment_processor_returns_no_payment_id(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willReturn('');

        $this->repository->rollbackTransaction()
            ->shouldBeCalled();

        $this->shouldThrow(SupermindPaymentIntentFailedException::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    public function it_should_rollback_a_supermind_request_for_cash_if_payment_processor_throws_an_exception(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willThrow(new SupermindPaymentIntentFailedException('error'));

        $this->repository->rollbackTransaction()
            ->shouldBeCalled();

        $this->shouldThrow(SupermindPaymentIntentFailedException::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    public function it_should_throw_payment_intent_failed_exception_on_card_exception_thrown(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';
        $paymentIntentId = 'pay_123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willReturn($paymentIntentId);

        $supermindRequest->setPaymentTxID($paymentIntentId)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willThrow(new CardException());

        $this->shouldThrow(SupermindPaymentIntentFailedException::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    public function it_should_throw_payment_intent_failed_exception_on_api_error_exception_thrown(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';
        $paymentIntentId = 'pay_123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willReturn($paymentIntentId);

        $supermindRequest->setPaymentTxID($paymentIntentId)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willThrow(new AuthenticationException());

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn('username');

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->shouldThrow(SupermindPaymentIntentFailedException::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    public function it_should_rethrow_generic_exception_on_generic_exception_thrown(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $receiverGuid = '123';
        $paymentIntentId = 'pay_123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest)
            ->shouldBeCalled()
            ->willReturn($paymentIntentId);

        $supermindRequest->setPaymentTxID($paymentIntentId)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->repository->rollbackTransaction()
            ->shouldBeCalled();

        $this->shouldThrow(\Exception::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    public function it_should_add_a_supermind_request_for_offchain_tokens(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $receiverGuid = '123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupOffchainPayment($supermindRequest)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->commitTransaction()
            ->shouldBeCalled();

        $this->addSupermindRequest($supermindRequest, $paymentMethodId)->shouldBe(true);
    }

    public function it_should_cancel_a_supermind_request_for_tokens_if_adding_request_fails(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $receiverGuid = '123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupOffchainPayment($supermindRequest)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
            ->shouldBeCalled();

        $this->repository->rollbackTransaction()
            ->shouldBeCalled();

        $this->repository->commitTransaction()
            ->shouldBeCalled();

        $this->addSupermindRequest($supermindRequest, $paymentMethodId)->shouldBe(true);
    }

    public function it_should_throw_offchain_payment_failed_exception_on_offchain_wallet_insufficient_funds_exception_thrown(
        SupermindRequest $supermindRequest,
        User $sender,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $receiverGuid = '123';

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethodId);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getReceiverGuid($receiverGuid);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->acl->interact($receiver, $sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->paymentProcessor->setupOffchainPayment($supermindRequest)
            ->shouldBeCalled();

        $this->repository->addSupermindRequest($supermindRequest)
            ->shouldBeCalled()
            ->willThrow(new OffchainWalletInsufficientFundsException());

        $this->shouldThrow(SupermindOffchainPaymentFailedException::class)->duringAddSupermindRequest($supermindRequest, $paymentMethodId);
    }

    // acceptSupermindRequest

    public function it_should_accept_a_supermind_request_for_cash(
        SupermindRequest $supermindRequest,
        User $recipient
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $recipient->getMerchant()
            ->willReturn([
                'id' => 'test'
            ]);

        $this->paymentProcessor->setUser($recipient)
            ->shouldBeCalled();

        $this->setUser($recipient);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $recipient, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::ACCEPTED, $supermindRequestId)
            ->shouldBeCalled();

        $this->paymentProcessor->capturePaymentIntent($paymentTxid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->acceptSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_accept_a_supermind_request_for_tokens(
        SupermindRequest $supermindRequest,
        User $sender
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $sender, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::ACCEPTED, $supermindRequestId)
            ->shouldBeCalled();

        $this->paymentProcessor->creditOffchainPayment($supermindRequest)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->acceptSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_try_accept_a_supermind_request_but_throw_capture_fail_exception_if_payment_fails(
        SupermindRequest $supermindRequest,
        User $recipient
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $recipient->getMerchant()
            ->willReturn([
                'id' => 'test'
            ]);

        $this->paymentProcessor->setUser($recipient)
            ->shouldBeCalled();

        $this->setUser($recipient);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $recipient, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->paymentProcessor->capturePaymentIntent($paymentTxid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::FAILED_PAYMENT, $supermindRequestId)
            ->shouldBeCalled();

        $this->shouldThrow(SupermindPaymentIntentCaptureFailedException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_supermind_not_found_exception_on_accepting_a_request_when_no_supermind_is_found()
    {
        $supermindRequestId = '123';

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(SupermindNotFoundException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }


    public function it_should_throw_a_supermind_req_incorrect_exception_on_accepting_a_request_when_supermind_is_not_in_created_state(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::PENDING;

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->shouldThrow(SupermindRequestIncorrectStatusException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_supermind_expired_exception_on_accepting_a_request_when_supermind_is_expired_and_force_expiration_reimbursing_cash(
        SupermindRequest $supermindRequest,
        User $recipient
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentId = 'pay_123';

        $recipient->getMerchant()
            ->willReturn([
                'id' => 'test'
            ]);

        $this->paymentProcessor
            ->setUser($recipient)
            ->willReturn($this->paymentProcessor);

        $this->setUser($recipient);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentId);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestPaymentMethod::CASH);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestId);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->paymentProcessor->cancelPaymentIntent($paymentId)
            ->shouldBeCalled();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->shouldThrow(SupermindRequestExpiredException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_supermind_expired_exception_on_accepting_a_request_when_supermind_is_expired_and_force_expiration_reimbursing_tokens(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $txId = 'offchain:wire:123';

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestId);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
            ->shouldBeCalled()
            ->willReturn($txId);

        $this->repository->saveSupermindRefundTransaction($supermindRequestId, $txId)
            ->shouldBeCalled();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->shouldThrow(SupermindRequestExpiredException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_exception_on_accept_if_a_user_is_not_authorised_to_reply(
        SupermindRequest $supermindRequest,
        User $sender
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->paymentProcessor);

        $this->setUser($sender);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $sender, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(ForbiddenException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    // updateSupermindRequestStatus

    public function it_should_update_a_supermind_request()
    {
        $supermindRequestId = '123';
        $targetStatus = SupermindRequestStatus::from(1);

        $this->repository->updateSupermindRequestStatus($targetStatus, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateSupermindRequestStatus($supermindRequestId, $targetStatus);
    }

    public function it_should_throw_an_exception_on_update_if_there_is_a_failure()
    {
        $supermindRequestId = '123';
        $targetStatus = SupermindRequestStatus::from(1);

        $this->repository->updateSupermindRequestStatus($targetStatus, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(SupermindRequestStatusUpdateException::class)->duringUpdateSupermindRequestStatus($supermindRequestId, $targetStatus);
    }

    // revokeSupermindRequest

    public function it_should_revoke_a_supermind_request_for_cash(
        SupermindRequest $supermindRequest,
        User $sender
    ) {
        $supermindRequestId = '123';
        $senderGuid = '234';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $sender->isAdmin()
            ->shouldBeCalled()
            ->willReturn(false);

        $sender->getGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->paymentProcessor->cancelPaymentIntent($paymentTxid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REVOKED, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->revokeSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_throw_exception_when_performing_revoke_a_supermind_request_for_cash_when_request_not_found()
    {
        $supermindRequestId = '123';
        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(SupermindNotFoundException::class)->duringRevokeSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_exception_when_performing_revoke_a_supermind_request_for_cash_when_invalid_status(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::PENDING;

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->shouldThrow(SupermindRequestIncorrectStatusException::class)->duringRevokeSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_exception_when_performing_revoke_a_supermind_request_for_cash_when_request_is_expired(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(true);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestId);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->paymentProcessor->cancelPaymentIntent($paymentTxid)
            ->shouldBeCalled();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->shouldThrow(SupermindRequestExpiredException::class)->duringRevokeSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_exception_when_performing_revoke_a_supermind_request_for_cash_when_user_is_not_sender_or_admin(
        SupermindRequest $supermindRequest,
        User $sender
    ) {
        $supermindRequestId = '123';
        $senderGuid = '234';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $sender->isAdmin()
            ->shouldBeCalled()
            ->willReturn(false);

        $sender->getGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid.'321');

        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();

        $this->setUser($sender);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->shouldThrow(SupermindUnauthorizedSenderException::class)->duringRevokeSupermindRequest($supermindRequestId);
    }

    // rejectSupermindRequest

    public function it_should_reject_a_supermind_request_for_cash(
        SupermindRequest $supermindRequest,
        User $actor
    ) {
        $supermindRequestId = '123';
        $receiverGuid = '234';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $actor, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REJECTED, $supermindRequestId)
            ->shouldBeCalled();

        $this->paymentProcessor->cancelPaymentIntent($paymentTxid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->rejectSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_reject_a_supermind_request_for_tokens(
        SupermindRequest $supermindRequest,
        User $actor
    ) {
        $supermindRequestId = '123';
        $receiverGuid = '234';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $txId = 'offchain:wire:123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestId);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $actor, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REJECTED, $supermindRequestId)
            ->shouldBeCalled();

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
            ->shouldBeCalled()
            ->willReturn($txId);

        $this->repository->saveSupermindRefundTransaction($supermindRequestId, $txId)
            ->shouldBeCalled();

        $this->rejectSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_throw_a_not_found_exception_on_reject_if_there_is_a_failure()
    {
        $supermindRequestId = '123';

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(SupermindNotFoundException::class)->duringRejectSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_request_incorrect_status_exception_on_reject_if_there_is_a_failure(
        SupermindRequest $supermindRequest,
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::REJECTED;

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->shouldThrow(SupermindRequestIncorrectStatusException::class)->duringRejectSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_request_expired_exception_on_reject_if_there_is_a_failure(
        SupermindRequest $supermindRequest,
        User $actor
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(true);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestId);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->paymentProcessor->cancelPaymentIntent($paymentTxid)
            ->shouldBeCalled();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->shouldThrow(SupermindRequestExpiredException::class)->duringRejectSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_an_unauthorized_sender_exception_on_reject_if_there_is_a_failure(
        SupermindRequest $supermindRequest,
        User $actor
    ) {
        $supermindRequestId = '123';
        $receiverGuid = '234';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid + '123'); // different guid

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->shouldThrow(SupermindUnauthorizedSenderException::class)->duringRejectSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_forbidden_exception_on_revoke_if_there_is_a_failure(
        SupermindRequest $supermindRequest,
        User $actor
    ) {
        $supermindRequestId = '123';
        $receiverGuid = '234';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus->value);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->acl->write($supermindRequest, $actor, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(ForbiddenException::class)->duringRejectSupermindRequest($supermindRequestId);
    }

    // completeSupermindRequestCreation

    public function it_should_complete_supermind_request_creation_and_return_true_on_success(
        SupermindRequest $supermindRequest,
        Activity $activity,
        User $receiver
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';
        $receiverGuid = '345';

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $supermindRequest->getActivityGuid()
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->setActivityGuid($activityGuid)
            ->shouldBeCalled();

        $supermindRequest->setEntity($activity)
            ->shouldBeCalled();

        $supermindRequest->setReceiverEntity($receiver)
            ->shouldBeCalled();

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->repository->updateSupermindRequestActivityGuid($supermindRequestId, $activityGuid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onCompleteSupermindRequestCreation($supermindRequest)
            ->shouldBeCalled();

        $this->completeSupermindRequestCreation($supermindRequestId, $activityGuid)->shouldBe(true);
    }

    public function it_should_try_complete_supermind_request_creation_and_throw_exception_on_failure(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->repository->updateSupermindRequestActivityGuid($supermindRequestId, $activityGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->eventsDelegate->onCompleteSupermindRequestCreation($supermindRequest)
            ->shouldNotBeCalled();

        $this->shouldThrow(SupermindRequestCreationCompletionException::class)->duringCompleteSupermindRequestCreation($supermindRequestId, $activityGuid);
    }

    // completeAcceptSupermindRequest

    public function it_should_complete_accept_supermind_request_and_return_true_on_success(
        SupermindRequest $supermindRequest,
        Activity $activity,
        User $receiver
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';
        $receiverGuid = '345';

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $supermindRequest->getActivityGuid()
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $supermindRequest->getTwitterRequired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->setReplyActivityGuid($activityGuid)
            ->shouldBeCalled();

        $supermindRequest->setEntity($activity)
            ->shouldBeCalled();

        $supermindRequest->setReceiverEntity($receiver)
            ->shouldBeCalled();

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->repository->updateSupermindRequestReplyActivityGuid($supermindRequestId, $activityGuid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onAcceptSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->completeAcceptSupermindRequest($supermindRequestId, $activityGuid)->shouldBe(true);
    }

    public function it_should_try_complete_accept_request_and_throw_exception_on_failure(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $replyActivityGuid = '234';

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->repository->updateSupermindRequestReplyActivityGuid($supermindRequestId, $replyActivityGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->eventsDelegate->onAcceptSupermindRequest($supermindRequest)
            ->shouldNotBeCalled();

        $this->shouldThrow(SupermindRequestAcceptCompletionException::class)->duringCompleteAcceptSupermindRequest($supermindRequestId, $replyActivityGuid);
    }

    // deleteSupermindRequest

    public function it_should_delete_a_supermind_request()
    {
        $supermindRequestId = '123';

        $this->repository->deleteSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->deleteSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_throw_exception_when_deleting_a_supermind_request_if_not_successful()
    {
        $supermindRequestId = '123';

        $this->repository->deleteSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(SupermindRequestDeleteException::class)->duringDeleteSupermindRequest($supermindRequestId);
    }

    // getReceivedRequests

    public function it_should_get_received_requests_and_pass_opts(
        User $actor,
        SupermindRequest $supermindRequest1,
        SupermindRequest $supermindRequest2,
        SupermindRequest $supermindRequest3
    ) {
        $offset = 12;
        $limit = 24;
        $status = SupermindRequestStatus::PENDING;
        $actorGuid = '123';
        $returnIterator = new ArrayIterator([
            $supermindRequest1,
            $supermindRequest2,
            $supermindRequest3
        ]);

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($actorGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $this->repository->getReceivedRequests(
            receiverGuid: $actorGuid,
            offset: $offset,
            limit: $limit,
            status: $status
        )
            ->shouldBeCalled()
            ->willReturn($returnIterator);

        $this->getReceivedRequests($offset, $limit, $status)->shouldBeLike(new Response([
            $supermindRequest1,
            $supermindRequest2,
            $supermindRequest3
        ]));
    }

    // getSentRequests

    public function it_should_get_sent_requests_and_pass_opts(
        User $actor,
        SupermindRequest $supermindRequest1,
        SupermindRequest $supermindRequest2,
        SupermindRequest $supermindRequest3
    ) {
        $offset = 12;
        $limit = 24;
        $status = SupermindRequestStatus::PENDING;
        $actorGuid = '123';
        $returnIterator = new ArrayIterator([
            $supermindRequest1,
            $supermindRequest2,
            $supermindRequest3
        ]);

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($actorGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $this->repository->getSentRequests(
            senderGuid: $actorGuid,
            offset: $offset,
            limit: $limit,
            status: $status
        )
            ->shouldBeCalled()
            ->willReturn($returnIterator);

        $this->getSentRequests($offset, $limit, $status)->shouldBeLike(new Response([
            $supermindRequest1,
            $supermindRequest2,
            $supermindRequest3
        ]));
    }

    // countReceivedRequests

    public function it_should_count_received_requests(User $actor)
    {
        $status = SupermindRequestStatus::from(1);
        $actorGuid = '123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($actorGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $this->repository->countReceivedRequests(
            receiverGuid: $actorGuid,
            status: $status
        )
            ->shouldBeCalled()
            ->willReturn(3);

        $this->countReceivedRequests($status)->shouldBe(3);
    }

    // countSentRequests

    public function it_should_count_sent_requests(User $actor)
    {
        $status = SupermindRequestStatus::from(1);
        $actorGuid = '123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($actorGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $this->repository->countSentRequests(
            senderGuid: $actorGuid,
            status: $status
        )
            ->shouldBeCalled()
            ->willReturn(3);

        $this->countSentRequests($status)->shouldBe(3);
    }

    // getRequest

    public function it_should_get_a_singular_request(
        Activity $activity,
        User $receiver,
        SupermindRequest $supermindRequest,
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';
        $receiverGuid = '345';

        $this->paymentProcessor
            ->setUser($receiver)
            ->willReturn($this->paymentProcessor);

        $this->setUser($receiver);

        $this->acl->read(Argument::any(), Argument::any())
            ->willReturn(true);

        $supermindRequest->getActivityGuid()
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->setEntity($activity)
            ->shouldBeCalled();

        $supermindRequest->setReceiverEntity($receiver)
            ->shouldBeCalled();

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->getRequest($supermindRequestId)->shouldBe($supermindRequest);
    }

    public function it_should_throw_an_exception_if_unable_to_get_a_singular_request()
    {
        $supermindRequestId = '123';

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(SupermindNotFoundException::class)->duringGetRequest($supermindRequestId);
    }

    // expireRequests

    public function it_should_allow_cli_sapi_name_to_expire_requests_for_offchain_tokens()
    {
        $ids = [ '567' ];
        $txId = 'offchain:wire:123';
        $supermindRequestId = '567';
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $supermindRequest = (new SupermindRequest())
            ->setGuid($supermindRequestId)
            ->setPaymentMethod($paymentMethod);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->repository->getExpiredRequests(Argument::type('int'))
            ->shouldBeCalled()
            ->willYield([$supermindRequest]);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRING_IN_PROGRESS, $supermindRequest->getGuid())
            ->shouldBeCalledOnce();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequest->getGuid())
            ->shouldBeCalledOnce();

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
            ->shouldBeCalled()
            ->willReturn($txId);

        $this->repository->saveSupermindRefundTransaction($supermindRequest->getGuid(), $txId)
            ->shouldBeCalled();

        $this->repository->commitTransaction()
            ->shouldBeCalled();

        $this->expireRequests()->shouldBe(true);
    }

    public function it_should_expire_requests_for_offchain_tokens_and_skip_refund_if_no_user_found()
    {
        $exceptionMessage = 'User not found';
        $ids = [ '567', '678' ];
        $supermindRequestId1 = '567';
        $supermindRequestId2 = '678';
        $txid1 = 'offchain:0x1';
        $txid2 = 'offchain:0x2';
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;

        $supermindRequest1 = (new SupermindRequest())
            ->setGuid($supermindRequestId1)
            ->setPaymentMethod($paymentMethod);

        $supermindRequest2 = (new SupermindRequest())
            ->setGuid($supermindRequestId2)
            ->setPaymentMethod($paymentMethod);

        $returnIterator = new ArrayIterator([
            $supermindRequest1,
            $supermindRequest2
        ]);

        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->repository->getExpiredRequests(Argument::type('int'))
            ->shouldBeCalled()
            ->willYield([
                $supermindRequest1,
                $supermindRequest2,
            ]);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRING_IN_PROGRESS, Argument::type('string'))
            ->shouldBeCalledTimes(2);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequest1->getGuid())
            ->shouldBeCalledOnce();

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest1)
            ->shouldBeCalled();

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest2)
            ->shouldNotBeCalled();

        $this->paymentProcessor->refundOffchainPayment($supermindRequest1)
            ->shouldBeCalledOnce()
            ->willReturn($txid1);

        $this->paymentProcessor->refundOffchainPayment($supermindRequest2)
            ->shouldBeCalledOnce()
            ->willThrow(new UserNotFoundException($exceptionMessage));

        $this->repository->saveSupermindRefundTransaction($supermindRequestId1, $txid1)
            ->shouldBeCalledOnce();

        $this->repository->saveSupermindRefundTransaction($supermindRequestId2, $txid2)
            ->shouldNotBeCalled();

        $this->repository->commitTransaction()
            ->shouldBeCalledOnce();
        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $this->expireRequests()->shouldBe(true);
    }

    public function it_should_allow_cli_sapi_name_to_expire_requests_for_cash()
    {
        $supermindRequestId = '567';
        $supermindRequest = (new SupermindRequest())
            ->setGuid($supermindRequestId)
            ->setPaymentMethod(SupermindRequestPaymentMethod::CASH);


        $this->repository->beginTransaction()
            ->shouldBeCalled();

        $this->repository->getExpiredRequests(Argument::type('int'))
            ->shouldBeCalled()
            ->willYield([$supermindRequest]);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRING_IN_PROGRESS, $supermindRequest->getGuid())
            ->shouldBeCalledOnce();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequest->getGuid())
            ->shouldBeCalledOnce();

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
            ->shouldNotBeCalled();

        $this->repository->saveSupermindRefundTransaction($supermindRequestId, Argument::type('string'))
            ->shouldNotBeCalled();

        $this->repository->commitTransaction()
            ->shouldBeCalled();

        $this->expireRequests()->shouldBe(true);
    }

    public function it_should_return_true_if_no_expired_request_found_while_expiring_reqeusts()
    {
        $this->repository->getExpiredRequests(Argument::type('int'))
            ->shouldBeCalled()
            ->willYield([]);


        $this->expireRequests()->shouldBe(true);
    }

    public function it_should_rollback_transactions_on_error_refunding_requests()
    {
        $txId = 'offchain:wire:123';
        $supermindRequestId = '567';
        $supermindRequest = (new SupermindRequest())
            ->setGuid($supermindRequestId)
            ->setPaymentMethod(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN);

        $this->repository->beginTransaction()
            ->shouldBeCalledOnce();

        $this->repository->getExpiredRequests(SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD)
            ->shouldBeCalledOnce()
            ->willYield([$supermindRequest]);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRING_IN_PROGRESS, $supermindRequest->getGuid())
            ->shouldBeCalledOnce();

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
            ->shouldBeCalledOnce()
            ->willReturn($txId);

        $this->repository->saveSupermindRefundTransaction($supermindRequestId, $txId)
            ->shouldBeCalledOnce()
            ->willThrow(new \Exception('error'));

        $this->repository->rollbackTransaction()
            ->shouldBeCalledOnce();

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldNotBeCalled();

        $this->repository->commitTransaction()
            ->shouldNotBeCalled();

        $this->expireRequests()->shouldBe(true);
    }

    // getSupermindRequestsByStatus

    public function it_should_get_supermind_requests_by_status()
    {
        $status = 2;

        $supermindRequest1 = (new SupermindRequest())->setGuid('123');
        $supermindRequest2 = (new SupermindRequest())->setGuid('234');

        $returnIterator = new ArrayIterator([
            $supermindRequest1,
            $supermindRequest2
        ]);

        $this->repository->getRequestsByStatus($status)
            ->shouldBeCalled()
            ->willReturn($returnIterator);

        $this->getSupermindRequestsByStatus($status)->shouldBeAGenerator([
            $supermindRequest1,
            $supermindRequest2
        ]);
    }

    // isSupermindRequestRefunded

    public function it_should_check_if_supermind_request_was_refunded()
    {
        $supermindRequestId = '123';
        $this->repository->getSupermindRefundTransactionId($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn('0123');
        $this->isSupermindRequestRefunded($supermindRequestId)->shouldBe(true);
    }

    public function it_should_check_if_supermind_request_was_NOT_refunded()
    {
        $supermindRequestId = '123';
        $this->repository->getSupermindRefundTransactionId($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn('');
        $this->isSupermindRequestRefunded($supermindRequestId)->shouldBe(false);
    }
}
