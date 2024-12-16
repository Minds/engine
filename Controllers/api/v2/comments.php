<?php
/**
 * Minds Comments API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2;

use Minds\Api\Exportable;
use Minds\Core;
use Minds\Core\Data;
use Minds\Entities;
use Minds\Exceptions\BlockedUserException;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Helpers;
use Minds\Core\Sockets;
use Minds\Core\Security;
use Minds\Exceptions\ProhibitedDomainException;
use NotImplementedException;

class comments implements Interfaces\Api
{
    /**
     * Returns the comments
     * @param array $pages
     *
     * API:: /v1/comment/:entityGuid/:commentGuid/:path
     */
    public function get($pages)
    {
        $response = [];
        $guid = $pages[0];

        $parent_path = $pages[2] ?? "0:0:0";
        $isRootLevelParent = $parent_path === '0:0:0';

        if (isset($pages[1]) && $pages[1] != 0) {
            $manager = new Core\Comments\Manager();
            $comment = $manager->get($guid, $parent_path, $pages[1]);

            return Factory::response([
                'comments' => $comment ? [$comment] : [],
            ]);
        }

        $manager = new Core\Comments\Manager();

        $limit = $_GET['limit'] ?? 12;
        $loadNext = isset($_GET['load-next']) ? (string) $_GET['load-next'] : null;
        $loadPrevious = isset($_GET['load-previous']) ? (string) $_GET['load-previous'] : null;
        if ($loadPrevious === 'null') {
            $loadPrevious = null;
        }
        if ($loadNext === 'null') {
            $loadNext = null;
        }
        $descending = isset($_GET['desc']) ? !($_GET['desc'] === "false") : true;
        $focusedUrn = $_GET['focused_urn'] ?? null;

        $includeOffset = isset($_GET['include_offset']) ? !($_GET['include_offset'] === "false") : true;
        if ($focusedUrn) {
            $includeOffset = true;
        }

        $opts = [
            'entity_guid' => $guid,
            'parent_path' => $parent_path,
            'limit' => (int) $limit,
            'offset' => $loadNext ?: null,
            'include_offset' => $includeOffset,
            'token' => $loadPrevious ?: null,
            'descending' => $descending,
            'is_focused' => $focusedUrn && (strpos($focusedUrn, 'urn:') === 0),
            'exclude_pinned' => $isRootLevelParent
        ];

        $comments = $manager->getList($opts);

        $token = (string) $comments->getPagingToken();

        if ($descending) {
            // Reversed order output
            $comments = $comments->reverse();
        }

        // if this page is the last, return no offset
        if ($comments->isLastPage()) {
            $offset = '';
        } elseif ($descending) {
            // if it's not the last page and it's descending, return last comment guid
            $offset = count($comments) <= $limit ? '' : $comments[count($comments) - 1]->getGuid();
        } else {
            // if it's not the last page and it's NOT descending, return first comment guid
            $offset = count($comments) > 0 ? $comments[0]->getGuid() : '';
        }

        if (!$loadNext && !$descending) {
            $offset = '';
        }

        // if no previous page, inject pinned comments.
        if ($isRootLevelParent && !$loadPrevious && !$loadNext) {
            $comments = $manager->injectPinnedComments($comments, $opts);
        }

        $response['comments'] = Exportable::_($comments);

        $response['load-previous'] = $descending ? $token : $offset;
        $response['load-next'] = $descending ? $offset : $token;

        $response['socketRoomName'] = "comments:{$guid}:{$parent_path}";

        return Factory::response($response);
    }

    public function post($pages)
    {
        throw new NotImplementedException();
    }

    public function put($pages)
    {
        throw new NotImplementedException();
    }

    public function delete($pages)
    {
        throw new NotImplementedException();
    }
}
