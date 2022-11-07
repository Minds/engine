<?php

namespace Minds\Core\Settings;

use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Settings\Manager');
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exceptions\UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function getSettings(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $settings = $this->manager
            ->setUser($loggedInUser)
            ->getUserSettings();

        return new JsonResponse($settings->export());
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exceptions\UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function storeSettings(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $this->manager
            ->setUser($loggedInUser)
            ->storeUserSettings($request->getParsedBody());

        return new JsonResponse("");
    }
}
