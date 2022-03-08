<?php
namespace Minds\Core\Experiments;

use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Experiments Controller.
 * @package Minds\Core\Experiments
 */
class Controller
{
    /**
     * Constructor for controller.
     * @param ?Manager $manager - experiments manager.
     */
    public function __construct(protected ?Manager $manager = null)
    {
        $this->manager = $manager ?? Di::_()->get('Experiments\Manager');
    }

    /**
     * Returns true if experiment is on.
     * @param ServerRequest $request - request containing experiment_id parameter.
     * @return JsonResponse - response containing status and 'is_on'.
     */
    public function isOn(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');
        $experimentId = $parameters['id'];
        $isOn = $this->manager->isOn($experimentId);

        return new JsonResponse([
            'status' => 'success',
            'is_on' => $isOn,
        ]);
    }
}
