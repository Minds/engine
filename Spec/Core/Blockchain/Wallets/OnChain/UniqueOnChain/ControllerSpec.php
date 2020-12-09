<?php

namespace Spec\Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Controller;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Manager;
use Minds\Core\Blockchain\Wallets\Onchain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_get_default_request(
        ServerRequest $request
    ) {
        $user = new User();
        $user->eth_wallet = '0xADDR';

        $request->getAttribute('_user')
            ->willReturn($user);

        $this->manager->isUnique($user)
            ->willReturn(true);

        $response = $this->get($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'unique' => true,
            'address' => '0xADDR'
        ]));
    }

    public function it_should_get_all_addresses_request(
        ServerRequest $request
    ) {
        $this->manager->getAll()
            ->willReturn(new Response([
                (new UniqueOnChainAddress)
                    ->setAddress('0x1')
                    ->setUserGuid('123'),
                (new UniqueOnChainAddress)
                    ->setAddress('0x2')
                    ->setUserGuid('456')
            ]));

        $response = $this->getAll($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'addresses' => [
                [
                    'address' => '0x1',
                    'user_guid' => '123'
                ],
                [
                    'address' => '0x2',
                    'user_guid' => '456'
                ]
            ]
        ]));
    }

    public function it_should_validate(
        ServerRequest $request
    ) {
        $user = new User();
        $user->guid = '123';
        $user->eth_wallet = '0xADDR';

        $request->getAttribute('_user')
            ->willReturn($user);

        $request->getParsedBody()
            ->willReturn([
                'address' => '0xADDR',
                'payload' => "{}",
                'signature' => '0xSIG',
            ]);

        $this->manager->add(Argument::that(function ($address) {
            return $address->getAddress() === '0xADDR'
                && $address->getUserGuid() === '123';
        }))
            ->willReturn(true);

        $this->validate($request);
    }

    public function it_should_remove_validate(
        ServerRequest $request
    ) {
        $user = new User();
        $user->guid = '123';
        $user->eth_wallet = '0xADDR';

        $request->getAttribute('_user')
            ->willReturn($user);

        $this->manager->delete(Argument::that(function ($address) {
            return $address->getAddress() === '0xADDR'
                && $address->getUserGuid() === '123';
        }))
            ->willReturn(true);

        $this->unValidate($request);
    }
}
