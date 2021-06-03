<?php
namespace Minds\Core\Votes;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Votes Controller
 * @package Minds\Core\Votes
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(Manager $manager = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->manager = $manager ?? new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getList(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $limit = (int) ($queryParams['limit'] ?? 12);
        $direction = $queryParams['direction'] ?? 'up';
        $entityGuid = $request->getAttribute('parameters')['entityGuid'] ?? null;
        $nextPage = $queryParams['next-page'] ?? null;

        if (!$entityGuid) {
            throw new UserErrorException('entityGuid must be supplied in the URI');
        }

        $opts = new VoteListOpts();
        $opts->setDirection($direction)
            ->setEntityGuid($entityGuid)
            ->setLimit($limit);

        if ($nextPage) {
            $opts->setPagingToken($nextPage);
        }

        $votes = iterator_to_array($this->manager->getList($opts));
        $nextPage = count($votes) > 0 ? end($votes)->getPagingToken() : null;

        return new JsonResponse([
           'status' => 'success',
           'votes' => Exportable::_($votes),
           'load-next' => $nextPage,
        ]);
    }
}
