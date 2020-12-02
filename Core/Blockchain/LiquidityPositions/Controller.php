<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Features;
use Exception;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * LiquidityPostions Controller
 * @package Minds\Core\Blockchain\LiquidityPostions
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var Features\Manager */
    protected $featuresManager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null,
        $featuresManager = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
    }

    /**
     * Returns the
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function get(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $summary = $this->manager->setUser($user)
            ->getSummary();
         
        return new JsonResponse(array_merge([
            'status' => 'success',
        ], $summary->export()));
    }
}
