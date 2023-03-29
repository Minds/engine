<?php
declare(strict_types=1);

namespace Minds\Core\Helpdesk\Chatwoot;

use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Chatwoot Controller
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
    }

    /**
     * Gets HMAC for user for Chatwoot.
     * @param ServerRequest $request - server request.
     * @return JsonResponse - response containing HMAC.
     */
    public function getUserHmac(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        return new JsonResponse([
            'hmac' => $this->manager->getUserHmac($user)
        ]);
    }
}
