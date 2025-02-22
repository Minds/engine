<?php

namespace Spec\Minds\Core\Blockchain\Services;

use kornrunner\Keccak;
use Minds\Core\Blockchain\Config;
use Minds\Core\Blockchain\GasPrice;
use Minds\Core\Blockchain\Util;
use Minds\Core\Http\Curl\JsonRpc\Client as JsonRpc;
use Minds\Core\Util\BigNumber;
use MW3\Sha3;
use MW3\Sign;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EthereumSpec extends ObjectBehavior
{
    private $_config;
    private $_jsonRpc;
    private $_sign;
    private $_sha3;

    public function let(Config $config, JsonRpc $jsonRpc, Sign $sign, Sha3 $sha)
    {
        $this->_config = $config;
        $this->_jsonRpc = $jsonRpc;
        $this->_sign = $sign;
        $this->_sha3 = $sha;

        $this->beConstructedWith($config, $jsonRpc, $sign, $sha);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blockchain\Services\Ethereum');
    }

    public function it_should_request_to_ethereum()
    {
        $this->_config->get()->willReturn([
            'rpc_endpoints' => [Util::BASE_CHAIN_ID => '127.0.0.1'],
            'mw3' => '/dev/null'
        ]);

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_test',
            'params' => []
        ])->willReturn(['result' => ['foo' => 'bar']]);

        $this->request('eth_test')->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_throw_exception_on_error_request()
    {
        $this->_config->get()->willReturn([
            'rpc_endpoints' => [Util::BASE_CHAIN_ID => '127.0.0.1'],
            'mw3' => '/dev/null'
        ]);

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_err',
            'params' => []
        ])->willReturn([
            'error' => [
                'code' => '100',
                'message' => 'Testing'
            ]
        ]);

        $this->shouldThrow(new \Exception('[Ethereum] 100: Testing'))->duringRequest('eth_err');
    }

    public function it_should_throw_exception_when_there_is_no_request()
    {
        $this->_config->get()->willReturn([
            'rpc_endpoints' => [Util::BASE_CHAIN_ID => '127.0.0.1'],
            'mw3' => '/dev/null'
        ]);

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_err',
            'params' => []
        ])->willReturn(null);

        $this->shouldThrow(new \Exception('Server did not respond'))->duringRequest('eth_err');
    }

    public function it_should_return_sha3_from_string()
    {
        $this->_config->get()->willReturn([
            'rpc_endpoints' => [Util::BASE_CHAIN_ID => '127.0.0.1'],
            'mw3' => '/dev/null'
        ]);

        $this->_jsonRpc->post(Argument::type('string'), Argument::type('array'))
            ->willReturn(['result' => '00hello']);

        $this->sha3("hello")->shouldReturn("1c8aff950685c2ed4bc3174f3472287b56d9517b9c948127319a09a7a36deac8");
    }

    public function it_should_change_the_current_config()
    {
        $this->_config->setKey('mainnet')
            ->shouldBeCalled();

        $this->useConfig('mainnet')->shouldReturn($this->getWrappedObject());
    }

    public function it_should_encode_a_contract_method()
    {
        $this->encodeContractMethod('issue(address,uint256)', ['0x123', BigNumber::_(10 ** 18)->toHex(true)])
            ->shouldReturn('0x867904b400000000000000000000000000000000000000000000000000000000000001230000000000000000000000000000000000000000000000000de0b6b3a7640000');
    }

    public function it_should_fail_to_encode_a_contract_method_because_of_a_non_hex_param()
    {
        $this->shouldThrow(new \Exception('Ethereum::call only supports raw hex parameters'))
            ->during(
                'encodeContractMethod',
                ['issue(address,uint256)', ['123', BigNumber::_(10 ** 18)->toHex(true)]]
            );
    }

    public function it_should_run_a_raw_method_unsigned_call()
    {
        $this->_config->get()->willReturn([
            'rpc_endpoints' => [Util::BASE_CHAIN_ID => '127.0.0.1'],
            'mw3' => '/dev/null'
        ]);

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_call',
            'params' => [
                [
                    'to' => '0x123',
                    'data' => '0x2c383a9f',
                ],
                'latest'
            ]
        ])->willReturn(['result' => ['foo' => 'bar']]);

        $this->call('0x123', 'method()', [])->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_sign_a_transaction()
    {
        $transaction = [];
        $this->_sign->setPrivateKey('privateKey')
            ->shouldBeCalled()
            ->willReturn($this->_sign);

        $this->_sign->setTx(json_encode($transaction))
            ->shouldBeCalled()
            ->willReturn($this->_sign);

        $this->_sign->sign()
            ->shouldBeCalled()
            ->willReturn('signed');

        $this->sign('privateKey', $transaction)->shouldReturn('signed');
    }

    public function it_should_send_a_raw_transaction()
    {
        $transaction = [
            'from' => '0x123',
            'gasLimit' => '1000',
            'nonce' => 'nonce'
        ];

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_gasPrice',
            'params' => []
        ])->willReturn(['result' => '0x2540be400']);

        $this->_config->get()->willReturn([
            'rpc_endpoints' => [Util::BASE_CHAIN_ID => '127.0.0.1'],
            'mw3' => '/dev/null',
            'server_gas_price' => 100,
        ]);

        $this->_sign->setPrivateKey('privateKey')
            ->shouldBeCalled()
            ->willReturn($this->_sign);

        $this->_sign->setTx(json_encode(array_merge($transaction, ['chainId' => Util::BASE_CHAIN_ID, 'gasPrice' => '0x2540be400'])))
            ->shouldBeCalled()
            ->willReturn($this->_sign);

        $this->_sign->sign()
            ->shouldBeCalled()
            ->willReturn('signed');

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_sendRawTransaction',
            'params' => ['signed']
        ])->willReturn(['result' => ['foo' => 'bar']]);

        $this->sendRawTransaction('privateKey', $transaction)->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_fail_when_sending_raw_transaction_because_theres_no_from_param()
    {
        $transaction = [
            'gasLimit' => '1000',
            'nonce' => 'nonce'
        ];

        $this->_config->get()->willReturn([
            'rpc_endpoints' => [
                Util::BASE_CHAIN_ID => '127.0.0.1'
            ],
            'mw3' => '/dev/null',
            'server_gas_price' => 100,
        ]);

        $this->shouldThrow(new \Exception('Transaction must have `from` and `gasLimit`'))->during(
            'sendRawTransaction',
            ['privateKey', $transaction]
        );
    }

    public function it_should_fail_when_sending_raw_transaction_because_theres_no_from_gasLimit()
    {
        $transaction = [
            'from' => '0x123',
            'nonce' => 'nonce'
        ];

        $this->_config->get()->willReturn([
            'rpc_endpoints' => [
                Util::BASE_CHAIN_ID => '127.0.0.1'
            ],
            'mw3' => '/dev/null',
            'server_gas_price' => 100,
        ]);

        $this->shouldThrow(new \Exception('Transaction must have `from` and `gasLimit`'))->during(
            'sendRawTransaction',
            ['privateKey', $transaction]
        );
    }

    public function it_should_fail_when_sending_raw_transaction_because_theres_an_error_signing_the_transaction()
    {
        $transaction = [
            'from' => '0x123',
            'gasLimit' => '1000',
            'nonce' => 'nonce'
        ];

        $this->_config->get()->willReturn([
            'rpc_endpoints' => [
                Util::BASE_CHAIN_ID => '127.0.0.1'
            ],
            'mw3' => '/dev/null',
            'server_gas_price' => 100,
        ]);

        $this->_sign->setPrivateKey('privateKey')
            ->shouldBeCalled()
            ->willReturn($this->_sign);

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_gasPrice',
            'params' => []
        ])->willReturn(['result' => '0x2540be400']);

        $this->_sign->setTx(json_encode(array_merge($transaction, ['chainId' => Util::BASE_CHAIN_ID, 'gasPrice' => '0x2540be400'])))
            ->shouldBeCalled()
            ->willReturn($this->_sign);

        $this->_sign->sign()
            ->shouldBeCalled()
            ->willReturn('');

        $this->shouldThrow(new \Exception('Error signing transaction'))->during(
            'sendRawTransaction',
            ['privateKey', $transaction]
        );
    }

    public function it_should_request_gas_price_via_rpc()
    {
        $this->_config->get()->willReturn([
            'rpc_endpoints' => [
                Util::BASE_CHAIN_ID => '127.0.0.1'
            ],
            'mw3' => '/dev/null'
        ]);

        $this->_jsonRpc->post(Argument::type('string'), [
            'method' => 'eth_gasPrice',
            'params' => []
        ])->willReturn(['result' => '0x123']);

        $this->getCurrentGasPrice()->shouldReturn('0x123');
    }
}
