<?php
namespace Minds\Core\Register;

use Minds\Exceptions\UserErrorException;
use Minds\Core\Register\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Register Controller
 * @package Minds\Core\Register
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
     * Checks if username has already been taken
     * Called by v3/register/validate
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function validate(ServerRequest $request): JsonResponse
    {
        $username = $request->getQueryParams()['username'] ?? null;

        if (!$username) {
            throw new UserErrorException("Username required");
        }

        $valid = $this->manager->validateUsername($username);

        return new JsonResponse([
            'status' => 'success',
            'valid' => $valid,
        ]);
    }
}
