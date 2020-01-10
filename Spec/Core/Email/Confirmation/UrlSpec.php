<?php

namespace Spec\Minds\Core\Email\Confirmation;

use Minds\Core\Config;
use Minds\Core\Email\Confirmation\Url;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UrlSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    public function let(
        Config $config
    ) {
        $this->config = $config;

        $this->beConstructedWith($config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Url::class);
    }

    public function it_should_generate(
        User $user
    ) {
        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://phpspec.minds.test/');

        $user->getEmailConfirmationToken()
            ->shouldBeCalled()
            ->willReturn('~token~');

        $this
            ->setUser($user)
            ->generate(['test' => 1, 'phpspec' => 'yes'])
            ->shouldReturn('https://phpspec.minds.test/email-confirmation?test=1&phpspec=yes&__e_cnf_token=%7Etoken%7E');
    }
}
