<?php

namespace Spec\Minds\Core\Router;

use Closure;
use Exception;
use Minds\Core\Di\Ref;
use Minds\Core\Router\Registry;
use Minds\Core\Router\Route;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Server\MiddlewareInterface;

class RouteSpec extends ObjectBehavior
{
    /** @var Registry */
    protected $registry;

    public function let(
        Registry $registry
    ) {
        $this->registry = $registry;

        $this->beConstructedWith($registry);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Route::class);
    }

    public function it_should_register(
        Ref $ref
    ) {
        $method = Route::ALLOWED_METHODS[0];

        $this->registry->register($method, 'phpspec/test/2', $ref, [])
            ->shouldBeCalled();

        $this
            ->register([$method], '/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }

    public function it_should_register_with_prefix_and_middleware(
        Ref $ref,
        MiddlewareInterface $middleware
    ) {
        $method = Route::ALLOWED_METHODS[0];

        $this->registry->register($method, 'prefix/0/phpspec/test/2', $ref, [
            $middleware
        ])
            ->shouldBeCalled();

        $this
            ->withPrefix('/prefix/0/')
            ->withMiddleware([$middleware])
            ->register([$method], '/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }

    public function it_should_throw_if_no_known_method_during_register(
        Ref $ref
    ) {
        $method = '~**INVALID HTTP METHOD**~';

        $this->registry->register($method, Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('Invalid method'))
            ->duringRegister([$method], '/phpspec/test/2/', $ref);
    }

    public function it_should_register_all(
        Ref $ref
    ) {
        $this->registry->register(Argument::type('string'), 'phpspec/test/2', $ref, [])
            ->shouldBeCalledTimes(count(Route::ALLOWED_METHODS));

        $this
            ->all('/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }

    public function it_should_register_get(
        Ref $ref
    ) {
        $this->registry->register('get', 'phpspec/test/2', $ref, [])
            ->shouldBeCalled();

        $this
            ->register(['get'], '/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }

    public function it_should_register_post(
        Ref $ref
    ) {
        $this->registry->register('post', 'phpspec/test/2', $ref, [])
            ->shouldBeCalled();

        $this
            ->register(['post'], '/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }

    public function it_should_register_put(
        Ref $ref
    ) {
        $this->registry->register('put', 'phpspec/test/2', $ref, [])
            ->shouldBeCalled();

        $this
            ->register(['put'], '/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }

    public function it_should_register_delete(
        Ref $ref
    ) {
        $this->registry->register('delete', 'phpspec/test/2', $ref, [])
            ->shouldBeCalled();

        $this
            ->register(['delete'], '/phpspec/test/2/', $ref)
            ->shouldReturn(true);
    }
}
