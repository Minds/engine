<?php declare(strict_types=1);
/**
 * JsonPayloadMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonPayloadMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    const JSON_MIME_TYPES = ['application/json', 'text/json', 'application/x-json'];

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
        $contentType = $this->_normalizeContentTypeEntry($request->getHeader('Content-Type'));

        if (in_array($contentType, static::JSON_MIME_TYPES, true)) {
            $request = $request
                ->withParsedBody(json_decode((string) $request->getBody(), true));
        }

        return $handler
            ->handle($request);
    }

    /**
     * @param array $values
     * @return mixed
     */
    protected function _normalizeContentTypeEntry(array $values): string
    {
        $fragments = explode(';', $values[0] ?? '');
        return $fragments[0];
    }
}
