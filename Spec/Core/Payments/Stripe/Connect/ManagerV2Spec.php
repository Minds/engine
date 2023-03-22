<?php

namespace Spec\Minds\Core\Payments\Stripe\Connect;

use Minds\Core\Entities\Actions\Save;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Connect\ManagerV2;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Stripe;
use Stripe\AccountLink;
use Stripe\Service\AccountLinkService;
use Stripe\Service\AccountService;

class ManagerV2Spec extends ObjectBehavior
{
    private $config;
    private $stripeClient;
    private $save;
    private $accountService;
    private $stripeAccount;

    public function let(
        Config $config,
        StripeClient $stripeClient,
        Save $save,
        AccountService $accountService,
        AccountLinkService $accountLinkService,
        Stripe\Account $stripeAccount,
    ) {
        $this->config = $config;
        $this->stripeClient = $stripeClient;
        $this->save = $save;
        $this->stripeAccount = $stripeAccount;

        // mocking stripe properties that contain their own
        // classes, that we want to mock.
        $accountService->create(Argument::type('array'))
            ->willReturn($stripeAccount);

        $accountService->retrieve(Argument::type('string'))
            ->willReturn($stripeAccount);

        $accountLink = new AccountLink();
        $accountLink->url = '@url';

        $accountLinkService->create(Argument::type('array'))
            ->willReturn($accountLink);

        $stripeClient->accounts = $accountService;
        $stripeClient->accountLinks = $accountLinkService;

        $this->beConstructedWith($config, $stripeClient, $save);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ManagerV2::class);
    }

    public function it_should_NOT_create_an_account_if_one_already_exists(User $user): void
    {
        $userMerchantId = '~merchant_id~';
        $userMerchant = [
            'service' =>  'stripe',
            'id' => $userMerchantId
        ];

        $user->getMerchant()
            ->shouldBeCalled()
            ->willReturn($userMerchant);
            
        $this->shouldThrow(
            new UserErrorException('A stripe account already exists for this user', 400)
        )->during('createAccount', [$user]);
    }

    public function it_should_create_an_account(User $user): void
    {
        $userMerchant = [];

        $user->getMerchant()
            ->shouldBeCalled()
            ->willReturn($userMerchant);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('test@email.com');

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');
        
        $user->setMerchant([
            'service' => 'stripe',
            'id' => null
        ])
            ->shouldBeCalled()
            ->willReturn();

        $this->save->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled();

        $this->createAccount($user)->shouldBe($this->stripeAccount);
    }

    public function it_should_get_an_account_by_user(User $user)
    {
        $userMerchantId = '~merchant_id~';
        $userMerchant = [
            'service' =>  'stripe',
            'id' => $userMerchantId
        ];

        $user->getMerchant()
            ->shouldBeCalled()
            ->willReturn($userMerchant);

        $this->getAccount($user)->shouldBe($this->stripeAccount);
    }

    public function it_should_get_account_link(User $user)
    {
        $userMerchantId = '~merchant_id~';
        $userMerchant = [
            'service' =>  'stripe',
            'id' => $userMerchantId
        ];

        $user->getMerchant()
            ->shouldBeCalled()
            ->willReturn($userMerchant);
        
        $this->config->get('site_url')
            ->shouldBeCalledTimes(2)
            ->willReturn('https://www.minds.com/');

        $this->getAccountLink($user);
    }
}
