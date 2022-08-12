<?php

namespace Spec\Minds\Core\Blockchain\Skale\Escrow;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Config;
use Minds\Core\Blockchain\Skale\Escrow\Manager;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class ManagerSpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;
    
    /** @var SkaleTools */
    private $skaleTools;

    /** @var Config */
    private $config;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        SkaleTools $skaleTools,
        Config $config
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->skaleTools = $skaleTools;
        $this->config = $config;
        
        $this->beConstructedWith(
            $entitiesBuilder,
            $skaleTools,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_send_a_boost_refund_tx(
        User $user,
        User $escrowUser
    ) {
        $amountWei = '10000000000';
        $context = 'boost_refund';
        $escrowUserGuid = '123123123';

        $this->setContext($context)
            ->setUser($user)
            ->setAmountWei($amountWei);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'boost_escrow_user_guid' => $escrowUserGuid
                ]
            ]);

        $this->entitiesBuilder->single($escrowUserGuid)
            ->shouldBeCalled()
            ->willReturn($escrowUser);

        $this->skaleTools->sendTokens(
            amountWei: $amountWei,
            sender: $escrowUser,
            receiver: $user,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: false
        )
            ->shouldBeCalled()
            ->willReturn('0x123');

        $participants = $this->send();

        $participants->getSender()->shouldBe($escrowUser);
        $participants->getReceiver()->shouldBe($user);
    }

    public function it_should_send_a_boost_charge_tx(
        User $user,
        User $escrowUser
    ) {
        $amountWei = '10000000000';
        $context = 'boost_charge';
        $escrowUserGuid = '123123123';

        $this->setContext($context)
            ->setUser($user)
            ->setAmountWei($amountWei);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'boost_escrow_user_guid' => $escrowUserGuid
                ]
            ]);

        $this->entitiesBuilder->single($escrowUserGuid)
            ->shouldBeCalled()
            ->willReturn($escrowUser);

        $this->skaleTools->sendTokens(
            amountWei: $amountWei,
            sender: $escrowUser,
            receiver: $user,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: false
        )
            ->shouldBeCalled()
            ->willReturn('0x123');

        $participants = $this->send();

        $participants->getSender()->shouldBe($escrowUser);
        $participants->getReceiver()->shouldBe($user);
    }

    public function it_should_send_a_boost_created_tx(
        User $user,
        User $escrowUser
    ) {
        $amountWei = '10000000000';
        $context = 'boost_created';
        $escrowUserGuid = '123123123';

        $this->setContext($context)
            ->setUser($user)
            ->setAmountWei($amountWei);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'boost_escrow_user_guid' => $escrowUserGuid
                ]
            ]);

        $this->entitiesBuilder->single($escrowUserGuid)
            ->shouldBeCalled()
            ->willReturn($escrowUser);

        $this->skaleTools->sendTokens(
            amountWei: $amountWei,
            sender: $user,
            receiver: $escrowUser,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: true
        )
            ->shouldBeCalled()
            ->willReturn('0x123');

        $participants = $this->send();

        $participants->getSender()->shouldBe($user);
        $participants->getReceiver()->shouldBe($escrowUser);
    }

    public function it_should_send_a_withdraw_refund_tx(
        User $user,
        User $escrowUser
    ) {
        $amountWei = '10000000000';
        $context = 'withdraw_refund';
        $escrowUserGuid = '123123123';

        $this->setContext($context)
            ->setUser($user)
            ->setAmountWei($amountWei);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'withdrawal_escrow_user_guid' => $escrowUserGuid
                ]
            ]);

        $this->entitiesBuilder->single($escrowUserGuid)
            ->shouldBeCalled()
            ->willReturn($escrowUser);

        $this->skaleTools->sendTokens(
            amountWei: $amountWei,
            sender: $escrowUser,
            receiver: $user,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: false
        )
            ->shouldBeCalled()
            ->willReturn('0x123');

        $participants = $this->send();

        $participants->getSender()->shouldBe($escrowUser);
        $participants->getReceiver()->shouldBe($user);
    }

    public function it_should_send_a_withdraw_created_tx(
        User $user,
        User $escrowUser
    ) {
        $amountWei = '10000000000';
        $context = 'withdraw_created';
        $escrowUserGuid = '123123123';

        $this->setContext($context)
            ->setUser($user)
            ->setAmountWei($amountWei);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'withdrawal_escrow_user_guid' => $escrowUserGuid
                ]
            ]);

        $this->entitiesBuilder->single($escrowUserGuid)
            ->shouldBeCalled()
            ->willReturn($escrowUser);

        $this->skaleTools->sendTokens(
            amountWei: $amountWei,
            sender: $user,
            receiver: $escrowUser,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: true
        )
            ->shouldBeCalled()
            ->willReturn('0x123');

        $participants = $this->send();

        $participants->getSender()->shouldBe($user);
        $participants->getReceiver()->shouldBe($escrowUser);
    }

    public function it_should_flip_negative_values_to_be_absolute(
        User $user,
        User $escrowUser
    ) {
        $amountWei = '-10000000000'; // NEGATIVE VALUE
        $amountWeiAbs = '10000000000';
        $context = 'withdraw_created';
        $escrowUserGuid = '123123123';

        $this->setContext($context)
            ->setUser($user)
            ->setAmountWei($amountWei);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'withdrawal_escrow_user_guid' => $escrowUserGuid
                ]
            ]);

        $this->entitiesBuilder->single($escrowUserGuid)
            ->shouldBeCalled()
            ->willReturn($escrowUser);

        $this->skaleTools->sendTokens(
            amountWei: $amountWeiAbs,
            sender: $user,
            receiver: $escrowUser,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: true
        )
            ->shouldBeCalled()
            ->willReturn('0x123');

        $participants = $this->send();

        $participants->getSender()->shouldBe($user);
        $participants->getReceiver()->shouldBe($escrowUser);
    }

    public function it_should_throw_exception_for_invalid_context()
    {
        $context = 'invalid';

        $this->setContext($context);

        $this->shouldThrow(ServerErrorException::class)
            ->duringSend();
    }
}
