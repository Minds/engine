<?php declare(strict_types=1);
/**
 * ErrorHandlerMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Exception;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use function Sentry\captureException;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /** @var bool */
    protected $sentryEnabled = true;

    /**
     * @param bool $sentryEnabled
     * @return ErrorHandlerMiddleware
     */
    public function setSentryEnabled(bool $sentryEnabled): ErrorHandlerMiddleware
    {
        $this->sentryEnabled = $sentryEnabled;
        return $this;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $message = 'Internal Server Error';
        $status = 500;

        try {
            return $handler
                ->handle($request);
        } catch (UnauthorizedException $e) {
            $message = 'Unauthorized';
            $status = 401;
        } catch (ForbiddenException $e) {
            $message = 'Forbidden';
            $status = 403;
        } catch (Exception $e) {
            // Log

            // TODO: Monolog
            error_log((string) $e);

            // Sentry

            if ($this->sentryEnabled) {
                captureException($e);
            }
        }

        switch ($request->getAttribute('accept')) {
            case 'html':
                return new HtmlResponse(sprintf('<h1>%s</h1>', $message), $status);

            case 'json':
            default:
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $message,
                ], $status);
        }
    }
}
