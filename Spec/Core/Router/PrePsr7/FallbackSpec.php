<?php

namespace Spec\Minds\Core\Router\PrePsr7;

use Minds\Core\Config;
use Minds\Core\Router\PrePsr7\Fallback;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FallbackSpec extends ObjectBehavior
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
        $this->shouldHaveType(Fallback::class);
    }

    public function it_should_check_if_should_route()
    {
        $this
            ->callOnWrappedObject('shouldRoute', [
                Fallback::ALLOWED[0]
            ])
            ->shouldReturn(true);

        $this
            ->callOnWrappedObject('shouldRoute', [
                Fallback::ALLOWED[count(Fallback::ALLOWED) - 1]
            ])
            ->shouldReturn(true);

        $this
            ->callOnWrappedObject('shouldRoute', [
                '~**INVALID FALLBACK ROUTE**~?'
            ])
            ->shouldReturn(false);
    }

    /**
     * The rest of this class is untestable due to the need of
     * instantiating PrePsr7\Router class on-the-fly.
     *
     * That's all, folks!
     */
}
