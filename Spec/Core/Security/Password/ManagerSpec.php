<?php

namespace Spec\Minds\Core\Security\Password;

use Minds\Core\Security\Password\Manager;
use Minds\Core\Security\Password\PwnedPasswords\Client;
use Exception;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_risk()
    {
        $password = "P@ssw0rd";
        $hashPrefix = "21BD1";
        $hashSuffix = "21BD12DC183F740EE76F27B78EB39C8AD972A757";

        $riskThreshold = 10;

        $responseRows = "2D8D1B3FAACCA6A3C6A91617B2FA32E2F57:1
2DC183F740EE76F27B78EB39C8AD972A757:57368
2DE4C0087846D223DBBCCF071614590F300:2";

        $responseArray = [
            [
                "hashSuffix"=> "2D8D1B3FAACCA6A3C6A91617B2FA32E2F57",
                "count"=> 1
            ],
                [
                "hashSuffix" => "2DC183F740EE76F27B78EB39C8AD972A757",
                "count" => 57368
            ],
                [
                "hashSuffix" => "2DE4C0087846D223DBBCCF071614590F300",
                "count" => 2
            ]
        ];


        $this->client->getRows($hashPrefix)
            ->shouldBeCalled()
            ->willReturn($responseRows);

        $this->getRisk($password)
            ->shouldReturn(true);
    }
}
