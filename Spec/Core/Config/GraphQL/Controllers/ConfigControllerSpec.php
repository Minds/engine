<?php

namespace Spec\Minds\Core\Config\GraphQL\Controllers;

use Minds\Core\Config\Config;
use Minds\Core\Config\GraphQL\Controllers\ConfigController;
use Minds\Core\Router\Exceptions\ForbiddenException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ConfigControllerSpec extends ObjectBehavior
{
    private Collaborator $configMock;

    public function let(Config $configMock)
    {
        $this->beConstructedWith($configMock);
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ConfigController::class);
    }

    public function it_should_return_valid_key()
    {
        $this->configMock->get('site_name')
            ->willReturn('phpspec');

        $this->getConfig('site_name')
            ->shouldBe('phpspec');
    }

    public function it_should_return_valid_two_level_key()
    {
        $this->configMock->get('theme_override')
            ->willReturn([
                'color_scheme' => 'dark'
            ]);

        $this->getConfig('theme_override.color_scheme')
            ->shouldBe('dark');
    }

    public function it_should_throw_error_on_invalid_key()
    {
        $this->configMock->get('invalid_key')
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringGetConfig('invalid_key');
    }
}
