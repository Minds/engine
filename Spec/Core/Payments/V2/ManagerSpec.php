<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\V2;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\V2\Manager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Payments\V2\Repository;
use Minds\Core\Referrals\ReferralCookie;
use Minds\Core\Wire\Wire;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $referralCookieMock;
    private Collaborator $loggerMock;

    public function let(
        Repository $repository,
        ReferralCookie $referralCookie,
        Logger $logger
    ): void {
        $this->repositoryMock = $repository;
        $this->referralCookieMock = $referralCookie;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $this->repositoryMock,
            $this->referralCookieMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->beAnInstanceOf(Manager::class);
    }

    /**
     * @param PaymentDetails $paymentDetails
     * @return void
     * @throws ServerErrorException
     */
    public function it_should_create_payment(
        PaymentDetails $paymentDetails
    ): void {
        $this->repositoryMock->createPayment($paymentDetails)
            ->shouldBeCalledOnce();

        $this->createPayment($paymentDetails);
    }

    public function it_should_create_payment_from_boost_with_referrer_cookie(
        Boost $boost,
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $boost->getPaymentMethod()
            ->shouldBeCalledOnce()
            ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $boost->getPaymentTxId()
            ->shouldBeCalledOnce()
            ->willReturn("123");

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === 456;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromBoost($boost);
    }

    public function it_should_create_payment_from_boost_with_decimal_payment_amount_with_referrer_cookie(
        Boost $boost,
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $boost->getPaymentMethod()
            ->shouldBeCalledOnce()
            ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1.23);

        $boost->getPaymentTxId()
            ->shouldBeCalledOnce()
            ->willReturn("123");

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === 456;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromBoost($boost);
    }

    public function it_should_create_payment_from_boost_without_referrer_cookie_with_referrer(
        Boost $boost,
        User $user
    ): void {
        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $user
            ->get('referrer')
            ->willReturn('123');
        $user
            ->get('time_created')
            ->willReturn(time());

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $boost->getPaymentMethod()
            ->shouldBeCalledOnce()
            ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $boost->getPaymentTxId()
            ->shouldBeCalledOnce()
            ->willReturn("123");

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === 123;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromBoost($boost);
    }

    public function it_should_create_payment_from_boost_without_referrer_cookie_without_referrer(
        Boost $boost,
        User $user
    ): void {
        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $user
            ->get('referrer')
            ->willReturn(null);
        $user
            ->get('time_created')
            ->willReturn(time());

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $boost->getPaymentMethod()
            ->shouldBeCalledOnce()
            ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $boost->getPaymentTxId()
            ->shouldBeCalledOnce()
            ->willReturn("123");

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === null;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromBoost($boost);
    }

    public function it_should_create_payment_from_wire_with_referrer_cookie(
        Wire $wire,
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalledTimes(2)
            ->willReturn('123');

        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $wire->getSender()
            ->shouldBeCalledTimes(2)
            ->willReturn($user);

        $wire->getAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $wire->getTrialDays()
            ->willReturn(0);

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === 456;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromWire(
            wire: $wire,
            paymentTxId: "",
            isPlus: true,
            isPro: false,
            sourceActivity: null
        );
    }

    public function it_should_create_payment_from_wire_with_decimal_payment_amount_with_referrer_cookie(
        Wire $wire,
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalledTimes(2)
            ->willReturn('123');

        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $wire->getSender()
            ->shouldBeCalledTimes(2)
            ->willReturn($user);

        $wire->getAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1.23);

        $wire->getTrialDays()
            ->willReturn(0);

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === 456;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromWire(
            wire: $wire,
            paymentTxId: "",
            isPlus: true,
            isPro: false,
            sourceActivity: null
        );
    }

    public function it_should_create_payment_from_wire_without_referrer_cookie_with_referrer(
        Wire $wire,
        User $user
    ): void {
        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $user
            ->get('referrer')
            ->willReturn('123');
        $user
            ->get('time_created')
            ->willReturn(time());
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $wire->getSender()
            ->shouldBeCalledTimes(4)
            ->willReturn($user);

        $wire->getAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $wire->getTrialDays()
            ->willReturn(0);

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === 123;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromWire(
            wire: $wire,
            paymentTxId: "",
            isPlus: true,
            isPro: false,
            sourceActivity: null
        );
    }

    public function it_should_create_payment_from_wire_without_referrer_cookie_without_referrer(
        Wire $wire,
        User $user
    ): void {
        $this->referralCookieMock->getAffiliateGuid()
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->referralCookieMock->withRouterRequest(Argument::type(ServerRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->referralCookieMock);

        $user
            ->get('referrer')
            ->willReturn(null);
        $user
            ->get('time_created')
            ->willReturn(time());
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $wire->getSender()
            ->shouldBeCalledTimes(2)
            ->willReturn($user);

        $wire->getAmount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $wire->getTrialDays()
            ->willReturn(0);

        $this->repositoryMock->createPayment(
            Argument::that(
                function (PaymentDetails $paymentDetails): bool {
                    return $paymentDetails->affiliateUserGuid === null;
                }
            )
        )->shouldBeCalledOnce();

        $this->createPaymentFromWire(
            wire: $wire,
            paymentTxId: "",
            isPlus: true,
            isPro: false,
            sourceActivity: null
        );
    }
}
