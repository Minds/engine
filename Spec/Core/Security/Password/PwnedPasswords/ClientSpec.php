<?php

namespace Spec\Minds\Core\Security\Password\PwnedPasswords;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Security\Password\PwnedPasswords\Client;

class ClientSpec extends ObjectBehavior
{
    /** @var GuzzleClient */
    protected $httpClient;

    public function let(GuzzleClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
        $this->httpClient = $httpClient;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }
}
