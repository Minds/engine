<?php
namespace Minds\Core\Monetization\Partners;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

class Controllers
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Return balance
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getBalance(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        /** @var EarningsBalance */
        $balance = $this->manager->getBalance($user);

        return new JsonResponse([
            'status' => 'success',
            'balance' => $balance->export(),
        ]);
    }
}
