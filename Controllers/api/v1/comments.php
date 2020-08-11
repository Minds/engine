<?php
/**
 * Minds Comments API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Api\Exportable;
use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities;
use Minds\Exceptions\BlockedUserException;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Helpers;
use Minds\Core\Sockets;
use Minds\Core\Security;
use Minds\Core\Wire\Paywall\PaywallUserNotPaid;

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
        //Factory::isLoggedIn();
        $response = [];
        $guid = $pages[0];
        $parent_guid_l1 = $parent_guid_l2 = 0;

        if (isset($_GET['parent_guid_l1']) && $_GET['parent_guid_l1'] != 0) {
            $parent_guid_l1 = $_GET['parent_guid_l1'];
        }

        if (isset($_GET['parent_guid_l2'])  && $_GET['parent_guid_l2'] != 0) {
            $parent_guid_l2 = $_GET['parent_guid_l2'];
        }

        $parent_path = $pages[2] ?? "$parent_guid_l1:$parent_guid_l2:0";

        if (isset($pages[1]) && $pages[1] != 0) {
            $manager = new Core\Comments\Manager();
            $comment = $manager->get($guid, $parent_path, $pages[1]);

            return Factory::response([
                'comments' => $comment ? [$comment] : [],
            ]);
        }

        /*$entity = Entities\Factory::build($guid);

        if (!Security\ACL::_()->read($entity)) {
            $subtype = $entity->subtype ?: $entity->type;
            return Factory::response([
                'status' => 'error',
                'message' => "You don't have permission to view these comments as the owner has made the $subtype viewable only to themselves."
            ]);
        }*/

        $manager = new Core\Comments\Manager();

        $descending = isset($_GET['descending']) ? $_GET['descending'] !== 'false' : true;
        $comments = $manager->getList([
            'entity_guid' => $guid,
            'parent_path' => $parent_path,
            'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 5,
            'offset' => isset($_GET['offset']) ? $_GET['offset'] : null,
            'include_offset' => isset($_GET['include_offset']) ? !($_GET['include_offset'] === "false") : true,
            'token' => isset($_GET['token']) ? $_GET['token'] : null,
            'descending' => $descending,
        ]);

        if ($descending) {
            // Reversed order output
            $comments = $comments->reverse();
        }

        $response['comments'] = Exportable::_($comments);
        $response['load-previous'] = (string) $comments->getPagingToken();

        $response['socketRoomName'] = "comments:{$guid}:{$parent_path}";

        return Factory::response($response);
    }

    public function post($pages)
    {
        $manager = new Core\Comments\Manager();

        $response = [];
        $error = false;
        $emitToSocket = false;

        switch ($pages[0]) {
          case "update":
            $comment = $manager->getByLuid($pages[1]);

            if ($comment) {
                $canEdit = $comment->canEdit();

                if ($canEdit && $comment->getOwnerGuid() != Core\Session::getLoggedInUserGuid()) {
                    $canEdit = false;
                }
            }

            if (!$comment || !$canEdit) {
                $response = ['status' => 'error', 'message' => 'This comment can not be edited'];
                break;
            }

            $content = $_POST['comment'];

            // Odd fallback so we don't break mobile apps editing
            if (!$_POST['title'] && $_POST['description']) {
                $content = $_POST['description'];
            }

            if (!$content && !$_POST['attachment_guid']) {
                return Factory::response([
                'status' => 'error',
                'message' => 'You must enter a message'
              ]);
            }

            $comment->setBody($content);

            if (isset($_POST['mature'])) {
                $comment->setMature(!!$_POST['mature']);
            }

            $comment->setTimeUpdated(time());
            $comment->setEdited(true);

            try {
                $saved = $manager->update($comment);
                $error = !$saved;
            } catch (\Exception $e) {
                $error = true;
            }

            break;
          case is_numeric($pages[0]):
          default:
            $entity = Core\Di\Di::_()->get('EntitiesBuilder')->single($pages[0]);

            // if ($entity instanceof Entities\Activity && $entity->remind_object) {
            //     $entity = (object) $entity->remind_object;
            // }

            if (!$pages[0] || !$entity || $entity->type == 'comment') {
                return Factory::response([
                  'status' => 'error',
                  'message' => 'We could not find that post'
                ]);
            }

            if (method_exists($entity, 'getAllowComments') && !$entity->getAllowComments()) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Comments are disabled for this post'
                ]);
            }

            if (!$_POST['comment'] && !$_POST['attachment_guid']) {
                return Factory::response([
                  'status' => 'error',
                  'message' => 'You must enter a message'
                ]);
            }

            /*if (!Security\ACL::_()->write($entity)) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You do not have permission to comment on this post'
                ]);
            }*/

            $parent_guids = explode(':', $_POST['parent_path'] ?? '0:0:0');

            $comment = new Core\Comments\Comment();
            $comment
                ->setEntityGuid($entity->guid)
                ->setParentGuidL1($parent_guids[0] ?? 0)
                ->setParentGuidL2($parent_guids[1] ?? 0)
                ->setMature(isset($_POST['mature']) && $_POST['mature'])
                ->setOwnerObj(Core\Session::getLoggedInUser())
                ->setContainerGuid(Core\Session::getLoggedInUserGuid())
                ->setTimeCreated(time())
                ->setTimeUpdated(time())
                ->setBody($_POST['comment']);

            if (isset($_POST['parentGuidL1'])) {
                $comment->setParentGuidL1($_POST['parentGuidL1']);
            }

            if (isset($_POST['parentGuidL2'])) {
                $comment->setParentGuidL2($_POST['parentGuidL2']);
            }

            if ($entity instanceof Entities\Group) {
                if ($entity->isConversationDisabled()) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Conversation has been disabled for this group',
                    ]);
                }
                $comment->setGroupConversation(true);
            }

            // TODO: setHasChildren (for threaded)
            try {
                $saved = $manager->add($comment);

                if ($saved) {
                    // Defer emitting after processing attachments
                    $comment->setEphemeral(false);
                    $emitToSocket = true;
                    $response['comment'] = $comment->export();
                } else {
                    throw new \Exception('The comment couldn\'t be saved');
                }
            } catch (UnverifiedEmailException $e) {
                throw $e;
            } catch (BlockedUserException $e) {
                $error = true;

                $parentOwnerUsername = '';

                if (isset($entity->ownerObj['username'])) {
                    $parentOwnerUsername = "@{$entity->ownerObj['username']}";
                }

                $response = [
                    'status' => 'error',
                    'message' => "The comment couldn't be saved because {$parentOwnerUsername} has blocked you."
                ];
            } catch (PaywallUserNotPaid $e) {
                $error = true;

                $response = [
                    'status' => 'error',
                    'message' => "You do not meet the subscription tier requirements to comment on this activity."
                ];
            } catch (\Exception $e) {
                error_log($e);
                $error = true;

                $response = [
                    'status' => 'error',
                    'message' => "The comment couldn't be saved"
                ];
            }
        }

        $modified = false;

        if (!$error && isset($_POST['title']) && $_POST['title']) {
            $comment->setAttachment('title', $_POST['title']);
            $comment->setAttachment('blurb', $_POST['description']);
            $comment->setAttachment('perma_url', Helpers\Url::normalize($_POST['url']));
            $comment->setAttachment('thumbnail_src', $_POST['thumbnail']);

            $modified = true;
        }

        if (!$error && isset($_POST['attachment_guid']) && $_POST['attachment_guid']) {
            $attachment = entities\Factory::build($_POST['attachment_guid']);

            if ($attachment) {
                $attachment->title = $comment->getBody();
                $attachment->access_id = $comment->getAccessId();

                $mature = false;

                if ($attachment instanceof \Minds\Interfaces\Flaggable) {
                    $mature = !!$comment->isMature();

                    $attachment->setFlag('mature', $mature);
                }

                $attachment->save();

                $siteUrl = Core\Di\Di::_()->get('Config')->get('site_url');

                switch ($attachment->subtype) {
                    case "image":
                        $comment->setAttachment('custom_type', 'image');
                        $comment->setAttachment('custom_data', [
                            'guid' => (string) $attachment->guid,
                            'container_guid' => (string) $attachment->container_guid,
                            'src'=> $siteUrl . 'fs/v1/thumbnail/' . $attachment->guid,
                            'href'=> $siteUrl . 'media/' . $attachment->container_guid . '/' . $attachment->guid,
                            'mature' => $mature,
                            'width' => $attachment->width,
                            'height' => $attachment->height,
                        ]);
                        break;

                    case "video":
                        $comment->setAttachment('custom_type', 'video');
                        $comment->setAttachment('custom_data', [
                            'guid' => (string) $attachment->guid,
                            'container_guid' => (string) $attachment->container_guid,
                            'thumbnail_src' => $attachment->getIconUrl(),
                            'mature' => $mature
                        ]);
                        break;
                }

                $comment->setAttachment('attachment_guid', $attachment->guid);
                $modified = true;
            }
        }

        if ($modified) {
            $manager->update($comment);
            $response['comment'] = $comment->export();
        }

        // Emit at the end because of attachment processing
        if ($emitToSocket) {
            try {
                (new Sockets\Events())
                ->setRoom("comments:{$comment->getEntityGuid()}:{$comment->getParentPath()}")
                ->emit(
                    'comment',
                    (string) $comment->getEntityGuid(),
                    (string) $comment->getOwnerGuid(),
                    (string) $comment->getGuid()
                );
                // Emit to parent
                (new Sockets\Events())
                ->setRoom("comments:{$comment->getEntityGuid()}:{$comment->getParentPath()}")
                ->emit(
                    'reply',
                    (string) ($comment->getParentGuidL2() ?: $comment->getParentGuidL1())
                );
            } catch (\Exception $e) {
            }
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        $manager = new Core\Comments\Manager();

        $comment = $manager->getByLuid($pages[0]);

        if (!$comment) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Comment not found',
            ]);
        }

        if ($comment->canEdit()) {
            $manager->delete($comment);
            return Factory::response([]);
        }
        //check if owner of activity trying to remove
        $entity = Entities\Factory::build($comment->getEntityGuid());

        if ($entity->owner_guid == Core\Session::getLoggedInUserGuid()) {
            $manager->delete($comment, ['force' => true]);
            return Factory::response([]);
        }

        return Factory::response([
            'status' => 'error',
            'message' => 'You can not delete this comment',
        ]);
    }
}
