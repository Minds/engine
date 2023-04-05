<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\V2;

use Minds\Common\Cookie;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\V2\Manager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Payments\V2\Repository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $loggerMock;

    public function let(
        Repository $repository,
        Logger $logger
    ): void {
        $this->repositoryMock = $repository;
        $this->loggerMock = $logger;

        $this->beConstructedWith($this->repositoryMock, $this->loggerMock);
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
    ): void {
        (new Cookie())
            ->setName('referrer')
            ->setValue('456')
            ->create();

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

    public function it_should_create_payment_from_boost_without_referrer_cookie_with_referrer(
        Boost $boost,
        User $user
    ): void {
        $_COOKIE = [];

        $user
            ->get('time_created')
            ->willReturn(time());
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');
        $user->referrer = 123;

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledTimes(2)
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
        $_COOKIE = [];

        $user
            ->get('time_created')
            ->willReturn(time());
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->setUser($user);

        $boost->getOwnerGuid()
            ->shouldBeCalledTimes(2)
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
}
