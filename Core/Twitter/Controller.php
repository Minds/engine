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
     */
    public function generateTwitterOAuthAccessToken(ServerRequestInterface $request): RedirectResponse
    {
        $authorizationCode = $request->getQueryParams()['code'] ?? null;

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $this->manager->generateOAuthAccessToken($authorizationCode);

        return new RedirectResponse($this->manager->getStoredOAuthRedirectPath());
    }

    public function postTweet(ServerRequestInterface $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $response = $this->manager->postTweet($requestBody['tweet_text']);
        return new JsonResponse($response);
    }
}
