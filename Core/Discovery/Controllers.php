<?php
namespace Minds\Core\Discovery;

use Exception;
use Minds\Core\Discovery\ResponseBuilders\GetDiscoveryForYouResponseBuilder;
use Minds\Core\Discovery\Validators\GetDiscoveryForYouRequestValidator;
use Minds\Core\Recommendations\Manager as RecommendationsManager;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Api\Exportable;

class Controllers
{
    /** @var Manager */
    protected $manager;

    public function __construct(
        $manager = null,
        private ?RecommendationsManager $recommendationsManager = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->recommendationsManager ??= new RecommendationsManager();
    }

    /**
     * Controller for post trends (based on tag trends)
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws NoTagsException
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
        $nsfw = array_filter(explode(',', $queryParams['nsfw'] ?? '') ?: [], 'strlen');

        $entities = $this->manager->getSearch($query, $filter, $type, [
            'plus' => $plus,
            'nsfw' => $nsfw,
        ]);

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
        $wireSupportTier = $request->getQueryParams()['wire_support_tier'] ?? null;

        $tags = $this->manager->getTags(['wire_support_tier' => $wireSupportTier]);

        $entityGuid = $request->getQueryParams()['entity_guid'] ?? null;

        $activityRelated = $entityGuid ? $this->manager->getActivityRelatedTags($entityGuid) : null;

        try {
            $forYou = $this->manager->getTagTrends([ 'limit' => 12, 'plus' => false]);
        } catch (Exception $e) {
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

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     * @throws Exception
     */
    public function getForYou(ServerRequest $request): JsonResponse
    {
        /**
         * @var User
         */
        $loggedInUser = $request->getAttribute('_user');
        $requestValidator = new GetDiscoveryForYouRequestValidator($loggedInUser);
        $responseBuilder = new GetDiscoveryForYouResponseBuilder();

        if (!$requestValidator->validate($request->getQueryParams())) {
            return $responseBuilder->buildBadRequestResponse($requestValidator->getErrors());
        }

        $this->manager->setUser($loggedInUser);

        $results = $this->manager->getForYouWiderNetworkDiscoveryFeed($request->getQueryParams());

        return $responseBuilder->buildSuccessfulResponse($results, $request->getQueryParams()['limit']);
    }
}
