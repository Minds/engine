<?php
declare(strict_types=1);

namespace Minds\Core\Analytics;

use Minds\Core\Analytics\Clicks\Manager as ClicksManager;
use Minds\Core\Di\Di;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Analytics module controller.
 */
class Controller
{
    public function __construct(
        private ?ClicksManager $clicksManager = null
    ) {
        $this->clicksManager ??= Di::_()->get(ClicksManager::class);
    }

    /**
     * Track click.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function trackClick(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $entityGuid = $request->getAttribute("parameters")["entityGuid"];
        $clientMeta = $request->getParsedBody()['client_meta'];

        $this->clicksManager->trackClick(
            entityGuid: $entityGuid,
            user: $loggedInUser,
            clientMeta: $clientMeta
        );

        return new JsonResponse([]);
    }
}
