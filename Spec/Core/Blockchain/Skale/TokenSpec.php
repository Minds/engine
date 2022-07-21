<?php

namespace Spec\Minds\Core\Blockchain\Skale;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Services\Skale as SkaleClient;
use Minds\Core\Blockchain\Skale\Token;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;

class TokenSpec extends ObjectBehavior
{
    /** @var SkaleClient */
    private $client;

    /** @var Config */
    private $config;

    /** @var Logger */
    private $logger;

    private $tokenAddress = '0xtoken';

    public function let(
        SkaleClient $client,
        Config $config,
        Logger $logger
    ) {
        $config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'minds_token_address' => $this->tokenAddress
                ]
            ]);

        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    
        $this->beConstructedWith(
            $client,
            $config,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Token::class);
    }

    public function it_should_get_balance_of_when_there_is_balance()
    {
        $accountAddress = '0x00';
        $balanceResult = '0xAAA';

        $this->client->call($this->tokenAddress, 'balanceOf(address)', [$accountAddress], null)
            ->shouldBeCalled()
            ->willReturn($balanceResult);

        $this->balanceOf($accountAddress)->shouldBe('2730');
    }

    public function it_should_get_SFUEL_balance_of_when_there_is_NO_balance()
    {
        $accountAddress = '0x00';
        $balanceResult = '0x0';

        $this->client->call($this->tokenAddress, 'balanceOf(address)', [$accountAddress], null)
            ->shouldBeCalled()
            ->willReturn($balanceResult);

        $this->balanceOf($accountAddress)->shouldBe('0');
    }

    public function it_should_get_SFUEL_balance_of_when_there_is_balance()
    {
        $accountAddress = '0x00';
        $balanceResult = '0xAAA';

        $this->client->request('eth_getBalance', [$accountAddress, "latest"])
            ->shouldBeCalled()
            ->willReturn($balanceResult);

        $this->sFuelBalanceOf($accountAddress)->shouldBe('2730');
    }

    public function it_should_get_balance_of_when_there_is_NO_balance()
    {
        $accountAddress = '0x00';
        $balanceResult = '0x0';

        $this->client->request('eth_getBalance', [$accountAddress, "latest"])
            ->shouldBeCalled()
            ->willReturn($balanceResult);

        $this->sFuelBalanceOf($accountAddress)->shouldBe('0');
    }
}
