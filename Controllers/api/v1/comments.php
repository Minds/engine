<?php
/**
 * Minds Comments API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\RateLimits\RateLimitExceededException;
use Minds\Core\Sockets;
use Minds\Core\Wire\Paywall\PaywallUserNotPaid;
use Minds\Entities;
use Minds\Entities\CommentableEntityInterface;
use Minds\Exceptions\BlockedUserException;
use Minds\Exceptions\ProhibitedDomainException;
use Minds\Helpers;
use Minds\Interfaces;
use NotImplementedException;
use Zend\Diactoros\ServerRequestFactory;

class comments implements Interfaces\Api
{
    /**
     * Comments are read from v2 API
     */
    public function get($pages)
    {
        throw new NotImplementedException();
    }

    public function post($pages)
    {
        $manager = new Core\Comments\Manager();

        $response = [];
        $error = false;
        $emitToSocket = false;

        $request = ServerRequestFactory::fromGlobals();

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

                if (!$_POST['attachment_guid']) {
                    $comment->removeAttachments();
                }

                if (isset($_POST['mature'])) {
                    $comment->setMature(!!$_POST['mature']);
                }

                $comment->setTimeUpdated(time());
                $comment->setEdited(true);
                $comment->setClientMeta($request->getParsedBody()['client_meta'] ?? []);

                try {
                    $saved = $manager->update($comment);
                    $error = !$saved;
                } catch (ProhibitedDomainException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    $error = true;
                }

                break;
            case is_numeric($pages[0]):
            default:
                $entity = Core\Di\Di::_()->get('EntitiesBuilder')->single($pages[0]);

                if (!$pages[0] || !$entity || $entity->type == 'comment') {
                    return Factory::response([
                      'status' => 'error',
                      'message' => 'We could not find that post'
                    ]);
                }

                if (!$entity instanceof CommentableEntityInterface) {
                    Factory::response([
                        'status' => 'error',
                        'message' => 'You are unable to comment on this type of entity',
                    ]);
                    return;
                }

                if (!$entity->getAllowComments()) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'This user has disabled comments on their post'
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
                    ->setClientMeta($request->getParsedBody()['client_meta'] ?? [])
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
                } catch (ProhibitedDomainException $e) {
                    throw $e;
                } catch (BlockedUserException $e) {
                    $error = true;

                    $parentOwnerUsername = '';

                    if (isset($entity->ownerObj['username'])) {
                        $parentOwnerUsername = "@{$entity->ownerObj['username']}";
                    }

                    $reason = "The comment couldn't be saved because you can't interact with the post.";

                    // Is a reply
                    if ($comment->getPartitionPath() !== '0:0:0') {
                        $reason = "The comment couldn't be saved because you can't interact with the comment and/or post.";
                    }

                    $response = [
                        'status' => 'error',
                        'message' => $reason,
                    ];
                } catch (PaywallUserNotPaid $e) {
                    $error = true;

                    $response = [
                        'status' => 'error',
                        'message' => "You do not meet the subscription tier requirements to comment on this activity."
                    ];
                } catch (RateLimitExceededException $e) {
                    $response = [
                        'status' => 'error',
                        'message' => "Please wait before making another comment."
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

                (new Save())->setEntity($attachment)->save();

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

        $entity = Entities\Factory::build($comment->getEntityGuid());
        $directParentComment = $manager->getDirectParent($comment);
        $loggedInUserGuid = Core\Session::getLoggedInUserGuid();

        // check if owner of activity or direct parent comment owner are trying to remove.
        if (
            $entity->owner_guid == $loggedInUserGuid ||
            ($directParentComment && $directParentComment->getOwnerObj()['guid'] == $loggedInUserGuid)
        ) {
            $manager->delete($comment, ['force' => true]);
            return Factory::response([]);
        }

        return Factory::response([
            'status' => 'error',
            'message' => 'You can not delete this comment',
        ]);
    }
}
