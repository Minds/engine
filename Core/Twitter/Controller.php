<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * The controller for the Twitter module's endpoints
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Twitter\Manager');
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function requestTwitterOAuthToken(ServerRequestInterface $request): JsonResponse
    {
        $redirectPath = $request->getQueryParams()['redirectPath'];

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $url = $this->manager->getRequestOAuthAuthorizationCodeUrl();

        $this->manager->storeOAuthRedirectPath($redirectPath);

        return new JsonResponse(['authorization_url' => $url]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     * @throws InvalidArgumentException
     */
    public function generateTwitterOAuthAccessToken(ServerRequestInterface $request): RedirectResponse
    {
        $authorizationCode = $request->getQueryParams()['code'] ?? null;

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $redirectUrl = $this->manager->getStoredOAuthRedirectPath();

        if (!$authorizationCode) {
            return new RedirectResponse($redirectUrl);
        }

        $this->manager->generateOAuthAccessToken($authorizationCode);

        return new RedirectResponse($redirectUrl);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exceptions\TwitterDetailsNotFoundException
     */
    public function postTweet(ServerRequestInterface $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $response = $this->manager->postTweet($requestBody['tweet_text']);
        return new JsonResponse($response);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getUserConfig(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $this->manager->setUser($loggedInUser);

        $response = $this->manager->getDetails();
        return new JsonResponse($response->export());
    }
}
