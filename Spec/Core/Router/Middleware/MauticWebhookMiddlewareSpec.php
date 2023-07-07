<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Router\Middleware;

use Minds\Core\Config\Config;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Middleware\MauticWebhookMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\ServerRequest;

class MauticWebhookMiddlewareSpec extends ObjectBehavior
{
    private const MAUTIC_SHARED_SECRET_HEADER = 'X-Mautic-Shared-Secret-Header';

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MauticWebhookMiddleware::class);
    }

    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_forbidden_exception_when_no_header_found(
        ServerRequest $request,
        RequestHandlerInterface $handler
    ): void {
        $request->getHeader(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn([]);
        $this->shouldThrow(ForbiddenException::class)->during('process', [$request, $handler]);
    }

    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     * @param Config $mindsConfig
     * @return void
     */
    public function it_should_throw_forbidden_exception_when_header_found_but_value_mismatch(
        ServerRequest $request,
        RequestHandlerInterface $handler,
        Config $mindsConfig
    ): void {
        // Overwrite the config
        $mindsConfig->get('email')
            ->shouldBeCalledOnce()
            ->willReturn(['mautic' => ['shared_key' => '123']]);
        $this->beConstructedWith($mindsConfig);

        $request->getHeader(self::MAUTIC_SHARED_SECRET_HEADER)
            ->shouldBeCalledTimes(2)
            ->willReturn(['test']);

        $this->shouldThrow(ForbiddenException::class)->during('process', [$request, $handler]);
    }

    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     * @param Config $mindsConfig
     * @return void
     */
    public function it_should_throw_forbidden_exception_when_header_found_with_multi_values_but_value_mismatch(
        ServerRequest $request,
        RequestHandlerInterface $handler,
        Config $mindsConfig
    ): void {
        // Overwrite the config
        $mindsConfig->get('email')
            ->shouldBeCalledOnce()
            ->willReturn(['mautic' => ['shared_key' => '123']]);
        $this->beConstructedWith($mindsConfig);

        $request->getHeader(self::MAUTIC_SHARED_SECRET_HEADER)
            ->shouldBeCalledTimes(2)
            ->willReturn(['test', '123']);

        $this->shouldThrow(ForbiddenException::class)->during('process', [$request, $handler]);
    }

    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     * @param Config $mindsConfig
     * @return void
     * @throws \Exception
     */
    public function it_should_succeed_when_header_found_and_secret_match(
        ServerRequest $request,
        RequestHandlerInterface $handler,
        Config $mindsConfig
    ): void {
        // Overwrite the config
        $mindsConfig->get('email')
            ->shouldBeCalledOnce()
            ->willReturn(['mautic' => ['shared_key' => '123']]);
        $this->beConstructedWith($mindsConfig);

        $request->getHeader(self::MAUTIC_SHARED_SECRET_HEADER)
            ->shouldBeCalledTimes(2)
            ->willReturn(['123']);

        $handler->handle($request)->shouldBeCalledOnce();

        $this->process($request, $handler);
    }
}
