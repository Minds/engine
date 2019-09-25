<?php

namespace Spec\Minds\Core\Pro\Delegates;

use Exception;
use Minds\Core\Config;
use Minds\Core\Pro\Delegates\SetupRoutingDelegate;
use Minds\Core\Pro\Domain\EdgeRouters\EdgeRouterInterface;
use Minds\Core\Pro\Settings;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SetupRoutingDelegateSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var EdgeRouterInterface */
    protected $edgeRouter;

    public function let(
        Config $config,
        EdgeRouterInterface $edgeRouter
    ) {
        $this->config = $config;
        $this->edgeRouter = $edgeRouter;

        $this->beConstructedWith($config, $edgeRouter);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SetupRoutingDelegate::class);
    }

    public function it_should_setup_routing_on_update_with_default_subdomain(
        Settings $settings
    ) {
        $settings->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $settings->getDomain()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'subdomain_suffix' => 'phpspec.test',
            ]);

        $settings->setDomain('pro-1000.phpspec.test')
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->edgeRouter->initialize()
            ->shouldBeCalled()
            ->willReturn($this->edgeRouter);

        $this->edgeRouter->putEndpoint($settings)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->shouldNotThrow(Exception::class)
            ->duringOnUpdate($settings);
    }

    public function it_should_setup_routing_on_update_with_a_custom_domain(
        Settings $settings
    ) {
        $settings->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $settings->getDomain()
            ->shouldBeCalled()
            ->willReturn('routing-test.phpspec.test');

        $settings->setDomain(Argument::cetera())
            ->shouldNotBeCalled();

        $this->edgeRouter->initialize()
            ->shouldBeCalled()
            ->willReturn($this->edgeRouter);

        $this->edgeRouter->putEndpoint($settings)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->shouldNotThrow(Exception::class)
            ->duringOnUpdate($settings);
    }
}
