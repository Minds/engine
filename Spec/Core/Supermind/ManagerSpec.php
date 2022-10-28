<?php

namespace Spec\Minds\Core\Supermind;

use ArrayIterator;
use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\EntitiesBuilder;
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
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Core\Supermind\Repository;
use Minds\Core\Supermind\Manager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Stripe\Exception\CardException;
use Stripe\Exception\AuthenticationException;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repository;

    /** @var SupermindPaymentProcessor */
    private $paymentProcessor;

    /** @var EventsDelegate */
    private $eventsDelegate;

    /** @var ACL */
    private $acl;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function let(
        Repository $repository,
        SupermindPaymentProcessor $paymentProcessor,
        EventsDelegate $eventsDelegate,
        ACL $acl,
        EntitiesBuilder $entitiesBuilder,
    ) {
        $this->beConstructedWith(
            $repository,
            $paymentProcessor,
            $eventsDelegate,
            $acl,
            $entitiesBuilder
        );

        $this->repository = $repository;
        $this->paymentProcessor = $paymentProcessor;
        $this->eventsDelegate = $eventsDelegate;
        $this->acl = $acl;
        $this->entitiesBuilder = $entitiesBuilder;
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
        User $sender
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';
        
        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();
        
        $this->setUser($sender);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);

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
            
        $this->acl->write($supermindRequest, $sender, ['isReply' => true])
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
            ->willReturn($supermindStatus);

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
        User $sender
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentTxid = 'pay_123';
        
        $this->paymentProcessor->setUser($sender)
            ->shouldBeCalled();
        
        $this->setUser($sender);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);

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
            
        $this->acl->write($supermindRequest, $sender, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::ACCEPTED, $supermindRequestId)
            ->shouldBeCalled();

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
            ->willReturn($supermindStatus);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);
        
        $this->shouldThrow(SupermindRequestIncorrectStatusException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_a_supermind_expired_exception_on_accepting_a_request_when_supermind_is_expired_and_force_expiration_reimbursing_cash(
        SupermindRequest $supermindRequest
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;
        $paymentId = 'pay_123';

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);
        
        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentId);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestPaymentMethod::CASH);
            
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

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);
    
        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN);
            
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
            ->shouldBeCalled();

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $this->shouldThrow(SupermindRequestExpiredException::class)->duringAcceptSupermindRequest($supermindRequestId);
    }

    public function it_should_throw_exception_on_accept_if_a_user_is_not_authed_to_reply(
        SupermindRequest $supermindRequest,
        User $sender
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::CREATED;

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

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
        $targetStatus = 1;
        
        $this->repository->updateSupermindRequestStatus($targetStatus, $supermindRequestId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateSupermindRequestStatus($supermindRequestId, $targetStatus);
    }

    public function it_should_throw_an_exception_on_update_if_there_is_a_failure()
    {
        $supermindRequestId = '123';
        $targetStatus = 1;
        
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
            ->willReturn($supermindStatus);

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
            ->willReturn($supermindStatus);

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
            ->willReturn($supermindStatus);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(true);


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
            ->willReturn($supermindStatus);

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
            ->willReturn($supermindStatus);

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
        
        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();
        
        $this->setUser($actor);

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(false);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $this->repository->getSupermindRequest($supermindRequestId)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);
            
        $this->acl->write($supermindRequest, $actor, ['isReply' => true])
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REJECTED, $supermindRequestId)
            ->shouldBeCalled();

        $this->paymentProcessor->refundOffchainPayment($supermindRequest)
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

    public function it_should_throw_a_request_incorrect_exception_on_reject_if_there_is_a_failure(
        SupermindRequest $supermindRequest,
    ) {
        $supermindRequestId = '123';
        $supermindStatus = SupermindRequestStatus::REJECTED;

        $supermindRequest->getStatus()
            ->shouldBeCalled()
            ->willReturn($supermindStatus);

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
            ->willReturn($supermindStatus);

        $supermindRequest->isExpired()
            ->shouldBeCalled()
            ->willReturn(true);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentTxid);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

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
            ->willReturn($supermindStatus);

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
            ->willReturn($supermindStatus);

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
            receiverGuid: $actorGuid,
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
        $status = 1;
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
        $status = 1;
        $actorGuid = '123';

        $actor->getGuid()
            ->shouldBeCalled()
            ->willReturn($actorGuid);

        $this->paymentProcessor->setUser($actor)
            ->shouldBeCalled();

        $this->setUser($actor);

        $this->repository->countSentRequests(
            receiverGuid: $actorGuid,
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

    public function it_should_allow_cli_sapi_name_to_expire_requests()
    {
        $this->repository->expireSupermindRequests(SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD)
            ->shouldBeCalled();

        $this->expireRequests()->shouldBe(true);
    }
}
