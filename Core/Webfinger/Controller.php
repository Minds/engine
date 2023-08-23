<?php

declare(strict_types=1);

namespace Minds\Core\Webfinger;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller for the Webfinger module's endpoints
 */
class Controller
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder,
        private ?Config $config,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function get(ServerRequestInterface $request): JsonResponse
    {
        $resource = $request->getQueryParams()['resource'] ?? null;
        
        if (!$resource) {
            throw new UserErrorException("Invalid resource");
        }

        [$username, $domain] = explode('@', $resource);

        $username = str_replace('acct:', '', $username);

        if (strpos($domain, 'www.') === 0) {
            $domain = str_replace('www.', '', $domain);
            $resource = str_replace('www.', '', $resource);
        }

        if ($domain !== $this->config->get('did')['domain']) {
            throw new NotFoundException('Invalid domain');
        }
    
        // get the minds channel from the resource
        $user = $this->entitiesBuilder->getByUserByIndex(strtolower($username));

        if (!$user instanceof User) {
            throw new NotFoundException("$username not found");
        }

        $userUrl = $this->config->get('site_url') . $user->getUsername();
        $userActivityPubUrl = $this->config->get('site_url') . 'api/activitypub/users/' . $user->getGuid();

        return new JsonResponse([
            'subject' => $resource,
            'aliases' => [
                $userUrl,
            ],
            'links' => [
                [
                    'rel' => "http://webfinger.net/rel/profile-page",
                    'type' => 'text/html',
                    'href' => $userUrl,
                ],
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => $userActivityPubUrl,
                ],
            ]
        ]);
    }

}
