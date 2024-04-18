<?php
declare(strict_types=1);

namespace Minds\Core\SEO\Robots\Controllers;

use Minds\Core\SEO\Robots\Services\RobotsFileService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\TextResponse;

/**
 * Robots file controller.
 */
class RobotsFileController
{
    public function __construct(
        private readonly RobotsFileService $robotsFileService
    ) {
    }

    /**
     * Get robots SEO file.
     * @param ServerRequestInterface $request - request.
     * @return TextResponse - text response.
     */
    public function getRobotsSeoFile(ServerRequestInterface $request): TextResponse
    {
        return new TextResponse(
            $this->robotsFileService->getText(
                host: $request->getServerParams()['HTTP_HOST']
            )
        );
    }
}
