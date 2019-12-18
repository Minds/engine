<?php

namespace Spec\Minds\Core\Router;

use Minds\Core\Router\Registry;
use Minds\Core\Router\RegistryEntry;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Server\MiddlewareInterface;

class RegistrySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Registry::class);
    }

    public function it_should_register(
        MiddlewareInterface $middleware
    ) {
        $this
            ->register('post', '/phpspec', function () {
            }, [$middleware])
            ->shouldReturn($this);
    }

    public function it_should_get_best_match(
        MiddlewareInterface $middleware
    ) {
        $this
            ->register('get', '/phpspec/:id', null, [$middleware])
            ->register('get', '/phpspec/new', null, [$middleware])
            ->register('get', '/phpspec/', null, [$middleware])
            ->getBestMatch('get', '/phpspec/new')
            ->shouldBeARegistryEntryWithRoute('phpspec/new');
    }

    public function getMatchers(): array
    {
        return [
            'beARegistryEntryWithRoute' => function ($subject, $route) {
                if (!($subject instanceof RegistryEntry)) {
                    throw new FailureException(sprintf("%s is a RegistryEntry instance", get_class($subject)));
                }

                if ($subject->getRoute() !== $route) {
                    throw new FailureException(sprintf("[RegistryEntry:%s] routes to %s", $subject->getRoute(), $route));
                }

                return true;
            }
        ];
    }
}
