<?php

namespace Spec\Minds\Core\Router;

use Minds\Core\Router\RegistryEntry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RegistryEntrySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RegistryEntry::class);
    }

    public function it_should_set_route()
    {
        $this
            ->setRoute('/phpspec/:id/edit/')
            ->getRoute()
            ->shouldReturn('phpspec/:id/edit');
    }

    public function it_should_get_wildcard_route()
    {
        $this
            ->setRoute('/phpspec/:id/edit/')
            ->getWildcardRoute()
            ->shouldReturn('phpspec/*/edit');
    }

    public function it_should_get_depth()
    {
        $this
            ->setRoute('/')
            ->getDepth()
            ->shouldReturn(-1);

        $this
            ->setRoute('/phpspec')
            ->getDepth()
            ->shouldReturn(0);

        $this
            ->setRoute('/phpspec/:id/edit/')
            ->getDepth()
            ->shouldReturn(2);
    }

    public function it_should_get_specificity()
    {
        $this
            ->setRoute('/')
            ->getSpecificity()
            ->shouldReturn(1);

        $this
            ->setRoute('/phpspec')
            ->getSpecificity()
            ->shouldReturn(1);

        $this
            ->setRoute('/phpspec/:id/edit/')
            ->getSpecificity()
            ->shouldReturn(5);

        $this
            ->setRoute('/phpspec/random/edit')
            ->getSpecificity()
            ->shouldReturn(7);
    }

    public function it_should_match()
    {
        $this
            ->setRoute('/')
            ->matches('/')
            ->shouldReturn(true);

        $this
            ->setRoute('/')
            ->matches('/test')
            ->shouldReturn(false);

        $this
            ->setRoute('/phpspec/:id')
            ->matches('/phpspec/1000')
            ->shouldReturn(true);

        $this
            ->setRoute('/phpspec/:id')
            ->matches('/phpspec')
            ->shouldReturn(false);

        $this
            ->setRoute('/phpspec/:id')
            ->matches('/phpspec/9999/1000')
            ->shouldReturn(false);

        $this
            ->setRoute('/phpspec/:id/edit')
            ->matches('/phpspec/1000/edit')
            ->shouldReturn(true);

        $this
            ->setRoute('/phpspec/:id/edit')
            ->matches('/phpspec/1000')
            ->shouldReturn(false);

        $this
            ->setRoute('/phpspec/:id/edit')
            ->matches('/phpspec/9999/1000')
            ->shouldReturn(false);
    }

    public function it_should_extract()
    {
        $this
            ->setRoute('/phpspec/:id/edit')
            ->extract('/phpspec/9999/edit')
            ->shouldReturn(['id' => '9999']);

        $this
            ->setRoute('/phpspec/:id/edit/:timestamp')
            ->extract('/phpspec/9999/edit/1000000')
            ->shouldReturn(['id' => '9999', 'timestamp' => '1000000']);
    }
}
