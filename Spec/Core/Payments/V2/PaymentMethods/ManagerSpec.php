<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\V2\PaymentMethods;

use Minds\Common\Repository\Response;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardNotFoundException;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as StripePaymentMethodsManager;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\Stripe\PaymentMethods\PaymentMethod as StripePaymentMethod;
use Minds\Core\Payments\V2\Models\PaymentMethod;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Minds\Core\Payments\V2\PaymentMethods\Manager;
use Minds\Entities\User;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $stripePaymentMethodsManagerMock;
    private Collaborator $giftCardsManagerMock;
    private Collaborator $loggerMock;

    public function let(
        StripePaymentMethodsManager $stripePaymentMethodsManagerMock,
        GiftCardsManager $giftCardsManagerMock,
        Logger $loggerMock
    ): void {
        $this->stripePaymentMethodsManagerMock = $stripePaymentMethodsManagerMock;
        $this->giftCardsManagerMock = $giftCardsManagerMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $this->stripePaymentMethodsManagerMock,
            $this->giftCardsManagerMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->beAnInstanceOf(Manager::class);
    }

    public function it_should_get_payment_methods(
        User $user,
        StripePaymentMethod $paymentMethod1,
        StripePaymentMethod $paymentMethod2
    ): void {
        $productId = null;
        $userGuid = Guid::build();

        $user->getGuid()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $paymentMethod1 = new StripePaymentMethod();
        $paymentMethod1->setId('1');
        $paymentMethod1->setCardBrand('Visa');
        $paymentMethod1->setCardLast4('1234');
        $paymentMethod1->setCardExpires('12/2023');

        $paymentMethod2 = new StripePaymentMethod();
        $paymentMethod2->setId('2');
        $paymentMethod2->setCardBrand('Mastercard');
        $paymentMethod2->setCardLast4('2345');
        $paymentMethod2->setCardExpires('11/2023');
 
        $this->stripePaymentMethodsManagerMock->getList([
          'limit' => 12,
          'user_guid' => $userGuid
        ])->shouldBeCalled()
          ->willReturn(new Response([$paymentMethod1, $paymentMethod2]));

        $resultPaymentMethod1 = new PaymentMethod(
            id: '1',
            name: 'Visa ***1234 - 12/2023',
            balance: null
        );

        $resultPaymentMethod2 = new PaymentMethod(
            id: '2',
            name: 'Mastercard ***2345 - 11/2023',
            balance: null
        );

        $this->getPaymentMethods($user, $productId)
          ->shouldBeLike([$resultPaymentMethod1, $resultPaymentMethod2]);
    }

    public function it_should_get_payment_methods_with_free_admin_boost_if_admin_and_boost(
        User $user,
        StripePaymentMethod $paymentMethod1,
        StripePaymentMethod $paymentMethod2
    ): void {
        $productId = GiftCardProductIdEnum::BOOST;
        $userGuid = Guid::build();

        $user->isAdmin()
          ->shouldBeCalled()
          ->willReturn(true);

        $user->getGuid()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $paymentMethod1 = new StripePaymentMethod();
        $paymentMethod1->setId('1');
        $paymentMethod1->setCardBrand('Visa');
        $paymentMethod1->setCardLast4('1234');
        $paymentMethod1->setCardExpires('12/2023');

        $paymentMethod2 = new StripePaymentMethod();
        $paymentMethod2->setId('2');
        $paymentMethod2->setCardBrand('Mastercard');
        $paymentMethod2->setCardLast4('2345');
        $paymentMethod2->setCardExpires('11/2023');
 
        $this->giftCardsManagerMock->getUserBalanceForProduct(
            user: $user,
            productIdEnum: $productId
        )->willThrow(new GiftCardNotFoundException());

        $this->stripePaymentMethodsManagerMock->getList([
          'limit' => 12,
          'user_guid' => $userGuid
        ])->shouldBeCalled()
          ->willReturn(new Response([$paymentMethod1, $paymentMethod2]));

        $resultPaymentMethod1 = new PaymentMethod(
            id: 'free_admin_boost',
            name: 'Admin Boost (Free)',
            balance: null
        );
  
        $resultPaymentMethod2 = new PaymentMethod(
            id: '1',
            name: 'Visa ***1234 - 12/2023',
            balance: null
        );

        $resultPaymentMethod3 = new PaymentMethod(
            id: '2',
            name: 'Mastercard ***2345 - 11/2023',
            balance: null
        );

        $this->getPaymentMethods($user, $productId)
          ->shouldBeLike([$resultPaymentMethod1, $resultPaymentMethod2, $resultPaymentMethod3]);
    }


    public function it_should_get_payment_methods_without_free_admin_boost_if_NOT_admin_but_boost(
        User $user,
        StripePaymentMethod $paymentMethod1,
        StripePaymentMethod $paymentMethod2
    ): void {
        $productId = GiftCardProductIdEnum::BOOST;
        $userGuid = Guid::build();

        $user->isAdmin()
          ->shouldBeCalled()
          ->willReturn(false);

        $user->getGuid()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $paymentMethod1 = new StripePaymentMethod();
        $paymentMethod1->setId('1');
        $paymentMethod1->setCardBrand('Visa');
        $paymentMethod1->setCardLast4('1234');
        $paymentMethod1->setCardExpires('12/2023');

        $paymentMethod2 = new StripePaymentMethod();
        $paymentMethod2->setId('2');
        $paymentMethod2->setCardBrand('Mastercard');
        $paymentMethod2->setCardLast4('2345');
        $paymentMethod2->setCardExpires('11/2023');
 
        $this->giftCardsManagerMock->getUserBalanceForProduct(
            user: $user,
            productIdEnum: $productId
        )->willThrow(new GiftCardNotFoundException());

        $this->stripePaymentMethodsManagerMock->getList([
          'limit' => 12,
          'user_guid' => $userGuid
        ])->shouldBeCalled()
          ->willReturn(new Response([$paymentMethod1, $paymentMethod2]));
  
        $resultPaymentMethod1 = new PaymentMethod(
            id: '1',
            name: 'Visa ***1234 - 12/2023',
            balance: null
        );

        $resultPaymentMethod2 = new PaymentMethod(
            id: '2',
            name: 'Mastercard ***2345 - 11/2023',
            balance: null
        );

        $this->getPaymentMethods($user, $productId)
          ->shouldBeLike([$resultPaymentMethod1, $resultPaymentMethod2]);
    }
}
