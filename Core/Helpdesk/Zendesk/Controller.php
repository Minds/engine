<?php
namespace Minds\Core\Helpdesk\Zendesk;

use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Zendesk Controller
 * @package Minds\Core\Helpdesk\Zendesk
 */
class Controller
{
    /** @var Manager */
    private $manager;

    /** @var Config */
    private $config;

    public function __construct(
        $manager = null,
        $config = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Helpdesk\Zendesk\Manager');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Gets JWT Token
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function redirect(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');

        $payload = $this->manager->getJwt($user); // generate payload

        $routeConfig = $this->config->get('zendesk')['url'];

        // create url
        $url = $routeConfig['base'].$routeConfig['jwt_route'].'?jwt='.$payload;
        
        // redirect to zendesk with token if headers are not already sent.
        if (headers_sent()) {
            return new JsonResponse([
                'error' => 'An error has occurred redirecting to Zendesk'
            ]);
        } else {
            header('Location: ' . $url);
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
