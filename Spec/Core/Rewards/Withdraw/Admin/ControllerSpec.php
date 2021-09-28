<?php
namespace Spec\Minds\Core\Rewards\Withdraw\Admin;

use Minds\Core\Config;
use Minds\Core\Rewards\Withdraw\Admin\Manager;
use Minds\Core\Rewards\Withdraw\Admin\Controller;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var Config */
    protected $config;

    public function let(Manager $manager, Config $config)
    {
        $this->manager = $manager;
        $this->config = $config;

        $this->beConstructedWith($manager, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_call_to_add_a_missing_withdrawal(
        ServerRequest $request
    ) {
        $txid = '0x000000000000000000';

        $request->getParsedBody()->shouldBeCalled()->willReturn([
            'txid' => $txid
        ]);

        $this->manager->addMissingWithdrawal($txid)
            ->shouldBeCalled();

        $this->addMissingWithdrawal($request);
    }

    public function it_should_call_to_force_confirmation(
        ServerRequest $request
    ) {
        $userGuid = '123';
        $timestamp = '1529380737';
        $requestTxid = '0x000000000000000000';

        $request->getParsedBody()->shouldBeCalled()->willReturn([
            'user_guid' => $userGuid,
            'timestamp' => $timestamp,
            'request_txid' => $requestTxid,
        ]);

        $this->manager->get(Argument::any())->shouldBeCalled();
        $this->manager->forceConfirmation(Argument::any())->shouldBeCalled();

        $this->forceConfirmation($request);
    }

    public function it_should_call_to_redispatch_completed(
        ServerRequest $request
    ) {
        $userGuid = '123';
        $timestamp = '1529380737';
        $requestTxid = '0x000000000000000000';

        $request->getParsedBody()->shouldBeCalled()->willReturn([
            'user_guid' => $userGuid,
            'timestamp' => $timestamp,
            'request_txid' => $requestTxid,
        ]);

        $this->manager->get(Argument::any())->shouldBeCalled();
        $this->manager->redispatchCompleted(Argument::any())->shouldBeCalled();

        $this->redispatchCompleted($request);
    }

    public function it_should_call_to_garbage_collect()
    {
        $this->manager->runGarbageCollection()->shouldBeCalled();
        $this->runGarbageCollection();
    }

    public function it_should_call_to_garbage_collect_a_single_withdrawal(
        ServerRequest $request
    ) {
        $userGuid = '123';
        $timestamp = '1529380737';
        $requestTxid = '0x000000000000000000';

        $request->getParsedBody()->shouldBeCalled()->willReturn([
            'user_guid' => $userGuid,
            'timestamp' => $timestamp,
            'request_txid' => $requestTxid,
        ]);

        $this->manager->get(Argument::any())->shouldBeCalled();
        $this->manager->runGarbageCollectionSingle(Argument::any())->shouldBeCalled();
        $this->runGarbageCollectionSingle($request);
    }
}
