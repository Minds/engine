<?php

namespace Spec\Minds\Core\Blockchain\Services\Web3Services;

use Minds\Core\Config;
use GuzzleHttp;
use Minds\Core\Blockchain\Services\Web3Services\MindsWeb3Service;
use Minds\Core\Log\Logger;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;

class MindsWeb3ServiceSpec extends ObjectBehavior
{
    protected $httpClient;
    protected $logger;
    protected $config;

    public function let(
        ClientInterface $httpClient,
        Logger $logger,
        Config $config,
    ) {
        $this->beConstructedWith($httpClient, $logger, $config);

        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MindsWeb3Service::class);
    }

    public function it_should_call_to_get_encoded_function_data()
    {
        $functionSignature = 'balanceOf(address)';
        $params = [ '0x000000000000000000000000000000000000dead' ];
        $walletPublicKey = '0x0000000000000000000000000000000000000000';

        $this->preparedConfig($walletPublicKey);

        $this->httpClient->request(
            'POST',
            'http://0.0.0.0:3333/tx/encodeFunctionData',
            Argument::that(function ($args) use ($walletPublicKey, $functionSignature, $params) {
                return $args['headers']['X-ETH-NETWORK'] === 'rinkeby' &&
                    $args['headers']['X-AUTH-KEY'] === 'MGUwZTBkMjUyYjMxY2U0YTg4NDBjOWY3YjNmODc2YzAxZjcwNjZkOWJkZDNmYjI5MTMzY2NkYmUxMTA3ZjA4OA==' &&
                    $args['headers']['X-WALLET-ADDRESS'] === $walletPublicKey &&
                    $args['json']['functionSignature'] === $functionSignature &&
                    $args['json']['params'] === $params;
            })
        )
            ->shouldBeCalled()
            ->willReturn(new Response(
                200,
                [],
                '{"status":200,"data":"0x00"}'
            ));

        $this->getEncodedFunctionData($functionSignature, $params)
            ->shouldReturn('0x00');
    }

    public function it_should_call_to_sign_transaction()
    {
        $walletPublicKey = '0x0000000000000000000000000000000000000000';

        $transaction = [
            'to' => '0x000000000000000000000000000000000000dead',
            'from' => '0x0000000000000000000000000000000000000000'
        ];

        $this->preparedConfig($walletPublicKey);

        $this->httpClient->request(
            'POST',
            'http://0.0.0.0:3333/sign/tx',
            Argument::that(function ($args) use ($walletPublicKey, $transaction) {
                return $args['headers']['X-ETH-NETWORK'] === 'rinkeby' &&
                    $args['headers']['X-AUTH-KEY'] === 'MGUwZTBkMjUyYjMxY2U0YTg4NDBjOWY3YjNmODc2YzAxZjcwNjZkOWJkZDNmYjI5MTMzY2NkYmUxMTA3ZjA4OA==' &&
                    $args['headers']['X-WALLET-ADDRESS'] === $walletPublicKey &&
                    $args['json'] === $transaction;
            })
        )
            ->shouldBeCalled()
            ->willReturn(new Response(
                200,
                [],
                '{"status":200,"data":"0x00"}'
            ));

        $this->signTransaction($transaction)
            ->shouldReturn('0x00');
    }

    public function it_should_call_to_withdraw()
    {
        $walletPublicKey = '0x0000000000000000000000000000000000000000';
        $address = '0x000000000000000000000000000000000000dead';
        $userGuid = '123';
        $gas = '0x01';
        $amount = '200000000000000000000';

        $this->preparedConfig($walletPublicKey);

        $this->httpClient->request(
            'POST',
            'http://0.0.0.0:3333/withdraw/complete',
            Argument::that(function ($args) use (
                $walletPublicKey,
                $address,
                $userGuid,
                $gas,
                $amount
            ) {
                return $args['headers']['X-ETH-NETWORK'] === 'rinkeby';// &&
                $args['headers']['X-AUTH-KEY'] === 'MGUwZTBkMjUyYjMxY2U0YTg4NDBjOWY3YjNmODc2YzAxZjcwNjZkOWJkZDNmYjI5MTMzY2NkYmUxMTA3ZjA4OA==' &&
                    $args['headers']['X-WALLET-ADDRESS'] === $walletPublicKey &&
                    $args['json']['address'] === $address &&
                    $args['json']['userGuid'] === $userGuid &&
                    $args['json']['gas'] === $gas &&
                    $args['json']['amount'] === $amount;
            })
        )
            ->shouldBeCalled()
            ->willReturn(new Response(
                200,
                [],
                '{"status":200,"data":"0x00"}'
            ));

        $this->withdraw($address, $userGuid, $gas, $amount)
            ->shouldReturn('0x00');
    }

    private function preparedConfig($walletPublicKey)
    {
        $this->config->get('blockchain')->shouldBeCalled()->willReturn([
            'web3_service' => [
                'base_url' => 'http://0.0.0.0:3333/',
                'wallet_encryption_key' => '12345678901234567890123456789012',
            ],
            'client_network' => 4,
            'contracts' => [
                'withdraw' => [
                    'wallet_pkey' => '0x4d09aa10ec584ff102e5e2da96888ac0dc6048f2',
                    'wallet_address' => $walletPublicKey
                ]
            ]
        ]);
    }
}
