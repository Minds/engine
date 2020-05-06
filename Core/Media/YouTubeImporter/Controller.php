<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Api\Exportable;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var YTAuth */
    protected $ytAuth;

    /** @var YTSubscription */
    protected $ytSubscription;

    /** @var Config */
    protected $config;

    public function __construct(
        $manager = null,
        $ytAuth = null,
        $ytSubscription = null,
        $config = null
    ) {
        $this->manager = $manager ?: Di::_()->get('Media\YouTubeImporter\Manager');
        $this->ytAuth = $ytAuth ?? new YTAuth();
        $this->ytSubscription = $ytSubscription ?? new YTSubscription();
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * Requests a token so a User can connect to his YouTube account.
     * Called by v3/media/youtube-importer/oauth
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getToken(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'url' => $this->ytAuth->connect(),
        ]);
    }

    /**
     * Disconnects a YouTube account from a user
     * Called by v3/media/youtube-importer/oauth
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function disconnectAccount(ServerRequest $request): JsonResponse
    {
        $channelId = $request->getQueryParams()['channelId'];

        if (!isset($channelId)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing channelId',
            ]);
        }

        /** @var User $user */
        $user = $request->getAttribute('_user');

        try {
            $this->ytAuth->disconnect($user, $channelId);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }

    /**
     * Receives an access code and requests a token.
     * Called by v3/media/youtube-importer/oauth/redirect
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function receiveAccessCode(ServerRequest $request): JsonResponse
    {
        $token = null;
        $code = $request->getQueryParams()['code'];

        /** @var User $user */
        $user = $request->getAttribute('_user');

        if (!isset($code)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing code',
            ]);
        }

        $this->ytAuth->fetchToken($user, $code);

        // redirect back to the URL
        // TODO this should redirect to an URL with the youtube importer opened
        header('Location: ' . filter_var($this->config->get('site_url') . 'settings/canary/other/youtube-migration/dashboard/available?status=setup', FILTER_SANITIZE_URL));
        exit;
    }

    /**
     * Gets a list of videos by channelId and status (optional)
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getVideos(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();

        if (!isset($queryParams['channelId'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You must provide a channelId',
            ]);
        }

        $channelId = $queryParams['channelId'];
        $youtubeId = $queryParams['youtubeId'] ?? null;

        $status = $queryParams['status'] ?? null;

        $offset = $queryParams['nextPageToken'] ?? null;
        if ($offset === 'null' || !$offset) { // Frontend sends null
            $offset = null;
        }

        /** @var User $user */
        $user = $request->getAttribute('_user');

        try {
            $videos = $this->manager->getVideos([
                'user' => $user,
                'user_guid' => $user->guid,
                'youtube_id' => $youtubeId,
                'youtube_channel_id' => $channelId,
                'status' => $status,
                'offset' => $offset,
            ]);

            return new JsonResponse([
                'status' => 'success',
                'videos' => Exportable::_($videos),
                'nextPageToken' => $videos->getPagingToken(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Returns a count of videos by status
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getCount(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        return new JsonResponse([
            'status' => 'success',
            'counts' => $this->manager->getCount($user),
        ]);
    }

    /**
     * Imports a given YouTube video. You can only import videos that belong to your user.
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function import(ServerRequest $request): JsonResponse
    {
        $params = $request->getParsedBody();

        /** @var User $user */
        $user = $request->getAttribute('_user');

        if (!isset($params['channelId'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You must provide a channelId',
            ]);
        }

        $channelId = $params['channelId'];

        if (!isset($params['videoId'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You must provide a videoId',
            ]);
        }

        $videoId = $params['videoId'];

        $video = (new YTVideo())
            ->setChannelId($channelId)
            ->setVideoId($videoId)
            ->setOwner($user)
            ->setOwnerGuid($user->guid);

        try {
            $this->manager->import($video);
        } catch (\Exception $e) {
            error_log($e);
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }

    /**
     * Cancels a video import
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function cancel(ServerRequest $request): JsonResponse
    {
        $params = $request->getQueryParams();

        if (!isset($params['videoId'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You must provide a videoId',
            ]);
        }

        $user = $request->getAttribute('_user');

        $videoId = $params['videoId'];

        return new JsonResponse([
            'status' => 'status',
            'done' => $this->manager->cancel($user, $videoId)
        ]);
    }

    /**
     * Subscribe to push notifications for a given YouTube channel
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function subscribe(ServerRequest $request): JsonResponse
    {
        $params = $request->getQueryParams();

        if (!isset($params['channelId'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You must provide a channelId',
            ]);
        }

        /** @var User $user */
        $user = $request->getAttribute('_user');

        try {
            $done = $this->ytSubscription->update($user, $params['channelId'], true);

            return new JsonResponse([
                'status' => 'success',
                'done' => $done,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unsubscribe from push notifications for a given YouTube channel
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function unsubscribe(ServerRequest $request): JsonResponse
    {
        $params = $request->getQueryParams();

        if (!isset($params['channelId'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You must provide a channelId',
            ]);
        }

        /** @var User $user */
        $user = $request->getAttribute('_user');

        try {
            $done = $this->ytSubscription->update($user, $params['channelId'], false);

            return new JsonResponse([
                'status' => 'success',
                'done' => $done,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Webhook for YouTube push notifications
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws \IOException
     * @throws \InvalidParameterException
     */
    public function callback(ServerRequest $request): JsonResponse
    {
        $params = $request->getQueryParams();
        if (isset($params['hub_challenge'])) {
            echo $params['hub_challenge'];
            exit;
        }

        $xml = simplexml_load_string(file_get_contents('php://input'), 'SimpleXMLElement', LIBXML_NOCDATA);
        $videoId = substr((string) $xml->entry->id, 9);
        $channelId = substr((string) $xml->entry->author->uri, 32);

        $video = (new YTVideo())
            ->setVideoId($videoId)
            ->setChannelId($channelId);

        $this->ytSubscription->onNewVideo($video);

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
