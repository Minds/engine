<?php
namespace Minds\Core\Blockchain\Metrics;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Features;
use Exception;
use Minds\Api;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Metrics Controller
 * @package Minds\Core\Blockchain\Metrics
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

        /** @var int */
        $endTs = $request->getQueryParams()['endTs'] ?? time();

        /** @var int */
        $startTs = $request->getQueryParams()['startTs'] ?? strtotime('-24 hours', $endTs);

        $metrics = array_map(function ($metric) {
            return $metric->export();
        }, $this->manager
            ->setTimeBoundary($startTs, $endTs)
            ->getAll());
         
        return new JsonResponse(array_merge([
            'status' => 'success',
            'metrics' => $metrics,
        ]));
    }
}
