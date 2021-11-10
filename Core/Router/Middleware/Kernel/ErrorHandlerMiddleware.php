<?php declare(strict_types=1);
/**
 * ErrorHandlerMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Exception;
use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Exceptions\UserErrorException;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Uri;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /** @var Logger */
    protected $logger;

    /** @var Config */
    protected $config;

    /**
     * ErrorHandlerMiddleware constructor.
     * @param Logger $logger
     */
    public function __construct(
        $logger = null
    ) {
        $this->logger = $logger ?: Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
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
        $data = [];

        try {
            return $handler
                ->handle($request);
        } catch (UnverifiedEmailException $e) {
            $message = $e->getMessage();
            $status = 403;
            $data = [ 'must_verify' => true ];
        } catch (UnauthorizedException $e) {
            $message = 'Unauthorized';
            $status = 401;
        } catch (ForbiddenException $e) {
            $message = 'Forbidden';
            $status = 403;
        } catch (UserErrorException $e) {
            $message = $e->getMessage();
            $status = ((int) $e->getCode()) ?: 400;
            $data = [
                "errors" => Exportable::_($e->getErrors())
            ];
        } catch (ServerErrorException $e) {
            $message = $e->getMessage();
            $status = ((int) $e->getCode()) ?: 500;
        } catch (Exception $e) {
            // Log
            $this->logger->critical($e, ['exception' => $e]);
        }

        switch ($request->getAttribute('accept')) {
            case 'html':
                if ($status === 401) {
                    return $this->redirectToLogin($request);
                }

                return new HtmlResponse(sprintf('<h1>%s</h1>', $message), $status);

            case 'json':
            default:
                return new JsonResponse(array_merge($data, [
                    'status' => 'error',
                    'message' => $message,
                    'errorId' => str_replace('\\', '::', get_class($e)),
                ]), $status);
        }
    }

    /**
     * Perform a browser redirect if html content type
     * @param ServerRequestInterface $request
     * @return HtmlResponse
     */
    protected function redirectToLogin(ServerRequestInterface $request): HtmlResponse
    {
        $redirectUrl = rtrim($this->config->get('site_url'), '/')
                        . $request->getUri()->getPath()
                        . '?'
                        . http_build_query($request->getQueryParams());

        $authUrl = $this->config->get('site_url') . 'login?redirectUrl=' . urlencode($redirectUrl);
        $indexHtml = "<script>window.location.href = \"$authUrl\";</script>";

        return new HtmlResponse($indexHtml, 401);
    }
}
