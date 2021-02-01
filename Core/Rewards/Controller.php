<?php
namespace Minds\Core\Rewards;

use Brick\Math\BigDecimal;
use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Features;
use Exception;
use Minds\Api\Exportable;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Rewards Controller
 * @package Minds\Core\Rewards
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

        /** @var string */
        $date = $request->getQueryParams()['date'] ?? date('Y-m-d');

        $opts = (new RewardsQueryOpts())
            ->setUserGuid($user->getGuid())
            ->setDateTs(strtotime($date));

        $rewardsSummary = $this->manager->getSummary($opts);
         
        return new JsonResponse(array_merge([
            'status' => 'success',
        ], $rewardsSummary->export()));
    }
}
