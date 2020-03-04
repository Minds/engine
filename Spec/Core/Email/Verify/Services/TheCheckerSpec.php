<?php

namespace Spec\Minds\Core\Email\Verify\Services;

use Minds\Core\Email\Verify\Services\TheChecker;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Http\Curl\Client;

class TheCheckerSpec extends ObjectBehavior
{
    private $http;
    private $config;

    public function let(Client $http, Config $config)
    {
        $this->beConstructedWith($http, $config);
        $this->http = $http;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TheChecker::class);
    }

    public function it_should_return_true_if_whitelisted()
    {
        $this->isWhitelisted('hello@icloud.com')
            ->shouldReturn(true);
        $this->isWhitelisted('hello@me.com')
            ->shouldReturn(true);
        $this->isWhitelisted('hello@mac.com')
            ->shouldReturn(true);
    }

    public function it_should_return_false_if_not_whitelisted()
    {
        // NOT WHITELISTED.
        $this->isWhitelisted('hello@minds.com')
            ->shouldReturn(false);

        $this->isWhitelisted('hello@fakemail.com')
            ->shouldReturn(false);

        $this->isWhitelisted('hello@gmail.com')
            ->shouldReturn(false);

        $this->isWhitelisted('hello@hotmail.com')
            ->shouldReturn(false);
    }

    public function it_should_only_match_domain_names_at_end_of_string()
    {
        $this->isWhitelisted('hello@icloud.com.com')
            ->shouldReturn(false);
        
        $this->isWhitelisted('hello@icloud.com.')
            ->shouldReturn(false);
        
        $this->isWhitelisted('hello@icloud.com ')
            ->shouldReturn(false);
        
        $this->isWhitelisted('hello@mac.com.')
            ->shouldReturn(false);
    }
}
