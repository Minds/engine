<?php

namespace Spec\Minds\Core\Blockchain;

use Minds\Core\Blockchain\Contracts\ExportableContract;
use Minds\Core\Blockchain\Manager;
use Minds\Core\Blockchain\Util;
use Minds\Core\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    public function let(Config $config)
    {
        $this->config = $config;

        $this->config->get(Argument::is('blockchain'))
            ->shouldBeCalled()
            ->willReturn([
                'chain_id' => Util::BASE_CHAIN_ID,
                'token_addresses' => [
                    Util::BASE_CHAIN_ID => '0x123',
                ],
                'contracts' => [
                    'wire' => [
                        'contract_address' => '0x456',
                        'plus_address' => '0xPLUS',
                        'plus_guid' => 123,
                    ],
                    'withdraw' => ['contract_address' => '0x789', 'limit' => 1],
                    'boost' => ['contract_address' => '0x002', 'wallet_address' => '0x003']
                ],
                'boost_address' => '0x654',
                'network_address' => 'https://rinkeby.infura.io/',
                'client_network' => '1337',
                'wallet_address' => '0x132',
                'boost_wallet_address' => '0x213',
                'eth_rate' => 1,
                'default_gas_price' => 1,
                'server_gas_price' => 2,
            ]);

        $this->beConstructedWith($config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_a_contract()
    {
        $this->getContract('token')->shouldReturnAnInstanceOf(ExportableContract::class);
    }

    public function it_should_return_null_if_contract_wasnt_found()
    {
        $this->getContract('not_found')->shouldReturn(null);
    }

    public function it_should_get_public_settings()
    {
        $this->config->get('blockchain_override')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->getPublicSettings()
            ->shouldBeArray();
    }

    public function it_should_get_overrides()
    {
        $this->config->get(Argument::is('blockchain_override'))
            ->shouldBeCalled()
            ->willReturn([
                'production' => [
                    'client_id' => '1338',
                ]
            ]);

        $this->getOverrides()
            ->shouldReturn([
                'production' => [
                    'wallet_address' => "0x132",
                    'boost_wallet_address' => "0x003",
                    'plus_address' => '0xPLUS',
                    'default_gas_price' => 1,
                ]
            ]);
    }
}
