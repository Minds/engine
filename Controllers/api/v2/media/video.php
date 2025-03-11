<?php
/**
 * Minds Video Controller
 *
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v2\media;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Manager;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Session as CoreSession;
use Minds\Core\Sessions\Session;
use Minds\Interfaces;

class video implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        if (!CoreSession::isLoggedin()) {
            throw new ForbiddenException();
        }

        /** @var Manager */
        $videoManager = Di::_()->get('Media\Video\Manager');
        /** @var TranscodeStates */
        $transcodeStates = Di::_()->get('Media\Video\Transcoder\TranscodeStates');
        /** @var ACL */
        $acl = Di::_()->get('Security\ACL');

        $video = $videoManager->get($pages[0]);

        if (!$video || !$acl->read($video)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Video not found',
            ]);
        }

        $sources = $videoManager->getSources($video);
        $status = $transcodeStates->getStatus($video); // Currently not efficient as no caching

        // if we had at least one transcode just return completed, even if some transcodes fail
        if ($status === TranscodeStates::FAILED && count($sources)) {
            $status = TranscodeStates::COMPLETED;
        }

        Factory::response([
            'entity' => $video->export(),
            'sources' => Factory::exportable($sources),
            'poster' => $video->getIconUrl(),
            'transcode_status' => $status,
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        http_response_code(501);
        exit;
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        http_response_code(501);
        exit;
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        http_response_code(501);
        exit;
    }
}
