<?php
namespace Minds\Core\Monetization\EarningsOverview;

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
     * Return overview
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getOverview(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');

        $queryParams = $request->getQueryParams();
        $from = $queryParams['from'] ?? strtotime('midnight first day of this month');
        $to = $queryParams['to'] ?? time();

        $overview = $this->manager
            ->setUser($user)
            ->getOverview($from, $to);

        return new JsonResponse(array_merge([ 'status' => 'success',], $overview->export()));
    }
}


// <!-- {
//     "payouts": [
//         {
//             "label": "",
//             "description": "",
//             "volume": "",
//             "amount_cents": "",
//             "amount_local": ""
//         }
//     ],
//     "earnings": [
//         {
//             "product": "pro",
//             "items": [
//                 {
//                     "label": "",
//                     "description": "",
//                     "volume": "",
//                     "amount_cents": ""
//                 }
//             ]
//         },
//         {
//             "product": "plus",
//             "items": [ ]
//         }
//     ]

// } -->
