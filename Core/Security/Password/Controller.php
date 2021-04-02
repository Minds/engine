<?php
namespace Minds\Core\Security\Password;

use Exception;
use Minds\Core\Security\Password\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Password Controller
 * @package Minds\Core\Security\Password
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null
    ) {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Checks how risky a password is, based on passwords
     * pwned in previous breaches
     * @return JsonResponse
     * @throws Exception when no password provided
     *
     */
    public function getRisk(ServerRequest $request): JsonResponse
    {
        $body = $request->getParsedBody();
        $password = $body['password'];

        if (!$password) {
            throw new Exception("Password required");
        }

        $risk = $this->manager->getRisk($password);

        return new JsonResponse([
            'status' => 'success',
            'risk' => $risk
        ]);
    }
}
