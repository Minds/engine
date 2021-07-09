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
use Minds\Core\Session;

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

    /** @var Rewards\Withdraw\Manager */
    protected $withdrawManager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null,
        $featuresManager = null,
        $withdrawManager = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
        $this->withdrawManager = $withdrawManager ?? Di::_()->get('Rewards\Withdraw\Manager');
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

    /**
     * Constructs a response with a list of the logged in users withdrawals.
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getWithdrawals(ServerRequest $request): JsonResponse
    {
        $userGuid = $request->getAttribute('_user')->getGuid();

        $queryParams = $request->getQueryParams();

        $opts = [
            'user_guid' => $userGuid,
            'limit' => isset($queryParams['limit']) ? (int) $queryParams['limit'] : 12,
            'offset' => isset($queryParams['offset']) ? $queryParams['offset'] : '',
            'hydrate' => true,
        ];

        $withdrawals = $this->withdrawManager->getList($opts);

        return new JsonResponse([
            'withdrawals' => $withdrawals,
            'load-next' => $withdrawals->getPagingToken(),
        ]);
    }
}
