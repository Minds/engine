<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Supermind\Settings\Validators\SupermindUpdateSettingsRequestValidator;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Supermind Settings Controller.
 */
class Controller
{
    public function __construct(
        protected ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Supermind\Settings\Manager');
    }

    /**
     * Get settings for logged in user.
     * @param ServerRequestInterface $request - request object.
     * @return JsonResponse - response.
     */
    //    #[OA\Get(
    //        path: '/api/v3/supermind/settings',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //        ]
    //    )]
    public function getSettings(ServerRequestInterface $request): JsonResponse
    {
        $user = $request->getAttribute("_user");
        $settings = $this->manager
            ->setUser($user)
            ->getSettings();
        return new JsonResponse($settings);
    }

    /**
     * Store settings change for logged in user.
     * @throws UserErrorException - bad request.
     * @param ServerRequestInterface $request - request object.
     * @return JsonResponse - response.
     */
    //    #[OA\Post(
    //        path: '/api/v3/supermind/settings',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 400, description: "Bad Request"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //        ]
    //    )]
    public function storeSettings(ServerRequestInterface $request): JsonResponse
    {
        $user = $request->getAttribute("_user");

        $settings = $request->getParsedBody();

        $validator = new SupermindUpdateSettingsRequestValidator();
        if (!$validator->validate($settings)) {
            throw new UserErrorException(
                message: "An error was encountered whilst validating the request",
                code: 400,
                errors: $validator->getErrors()
            );
        }

        $this->manager->setUser($user)
            ->updateSettings($settings);

        return new JsonResponse([]);
    }
}
