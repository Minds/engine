<?php
namespace Minds\Core\Discovery;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Api\Exportable;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;

class Controllers
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null, $entitiesBuilder = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Controller for search requests
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getTrends(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $shuffle = $queryParams['shuffle'] ?? true;
        $plus = filter_var($queryParams['plus'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $tagLimit = 8;
        $postLimit = 10;

        $tagTrends = $this->manager->getTagTrends([
            'limit' => $tagLimit * 2,  //Return more tags than we need for posts to feed from
            'plus' => $plus
        ]);
        $postTrends = $this->manager->getPostTrends(array_map(function ($trend) {
            return "{$trend->getHashtag()}";
        }, $tagTrends), [
            'limit' => $postLimit,
            'plus' => $plus,
        ]);

        $hero = array_shift($postTrends);

        // $trends =  array_merge(array_slice($tagTrends, 0, $tagLimit), $postTrends);
        $trends = $postTrends;

        if ($shuffle) {
            shuffle($trends);
        }

        return new JsonResponse([
            'status' => 'success',
            'trends' => Exportable::_($trends),
            'hero' => $hero ? $hero->export() : null,
        ]);
    }

    /**
     * Controller for search requests
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getSearch(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? null;
        $filter = $queryParams['algorithm'] ?? 'latest';
        $type = $queryParams['type'] ?? '';
        $plus = filter_var($queryParams['plus'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $entities = $this->manager->getSearch($query, $filter, $type, [ 'plus' => $plus ]);

        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($entities),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Controller for getting tags
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getTags(ServerRequest $request): JsonResponse
    {
        $tags = $this->manager->getTags();

        $entityGuid = $request->getQueryParams()['entity_guid'] ?? null;

        $activityRelated = $entityGuid ? $this->manager->getActivityRelatedTags($entityGuid) : null;
    
        try {
            $forYou = $this->manager->getTagTrends([ 'limit' => 12, 'plus' => false]);
        } catch (\Exception $e) {
            $forYou = null;
        }

        return new JsonResponse([
            'status' => 'success',
            'tags' => $tags['tags'],
            'trending' => $tags['trending'],
            'default' => $tags['default'],
            'for_you' => $forYou ? Exportable::_($forYou) : null,
            'activity_related' => $activityRelated ? Exportable::_($activityRelated) : null
        ]);
    }

    /**
     * Set the tags a user wants to prefer
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function setTags(ServerRequest $request): JsonResponse
    {
        $body = $request->getParsedBody();
        $selected = $body['selected'] ?? [];
        $deselected = $body['deselected'] ?? [];

        $success = $this->manager->setTags($selected, $deselected);
        return new JsonResponse([ 'status' => $success ? 'success' : 'error', ]);
    }
}
