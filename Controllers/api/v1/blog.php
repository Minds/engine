<?php
/**
 * Minds Blog API
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Common\Access;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Helpers;
use Minds\Interfaces;
use Minds\Core\Blogs\Delegates\CreateActivity;
use Minds\Entities\User;

class blog implements Interfaces\Api
{
    /**
     * Returns the conversations or conversation
     * @param array $pages
     *
     * API:: /v1/blog/:filter
     */
    public function get($pages)
    {
        $response = [];

        if (!isset($pages[0])) {
            $pages[0] = "top";
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
        $offset = isset($_GET['offset']) ? (string) $_GET['offset'] : '';

        $repository = new Core\Blogs\Repository();
        $trending = new Core\Blogs\Trending();
        $manager = new Core\Blogs\Manager();
        $headerManager = new Core\Blogs\Header();

        switch ($pages[0]) {
            case "all":
                if (!Core\Session::isAdmin()) {
                    $response['entities'] = new Exportable([]);
                    $response['load-next'] = '';
                    break;
                }

                $blogs = $repository->getList([
                    'limit' => $limit,
                    'offset' => $offset,
                    'all' => true,
                ]);

                $response['entities'] = new Exportable($blogs);
                $response['load-next'] = $blogs->getPagingToken();
                break;

            case "trending":
            case "top":
                $blogs = $trending->getList([
                    'limit' => $limit,
                    'offset' => $offset,
                    'rating' => isset($_GET['rating']) ? (int) $_GET['rating'] : 1,
                ]);

                $response['entities'] = new Exportable($blogs);
                $response['load-next'] = $blogs->getPagingToken();
                break;

            case "network":
            case "owner":
                $opts = [
                    'limit' => $limit,
                    'offset' => $offset,
                ];

                $guid = isset($pages[1]) ? $pages[1] : Core\Session::getLoggedInUserGuid();

                if (isset($pages[1]) && !is_numeric($pages[1])) {
                    $lookup = new Core\Data\lookup();
                    $guid = key($lookup->get(strtolower($pages[1])));
                }

                if ($pages[0] === 'network') {
                    $opts['network'] = $guid;
                } elseif ($pages[0] === 'owner') {
                    $opts['container'] = $guid;
                }

                $blogs = $repository->getList($opts);

                $export = [];
                foreach ($blogs as $blog) {
                    if ($blog->getOwnerGuid() != Core\Session::getLoggedInUserGuid() && $blog->getAccessId() != Access::PUBLIC) {
                        continue;
                    }
                    $export[] = $blog;
                }
                //$export = array_slice($export, 0, $limit);

                $response['entities'] = new Exportable($export);
                $response['load-next'] = $blogs->getPagingToken();
                break;

            case "next":
                if (!isset($pages[1])) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Not blog reference guid provided'
                    ]);
                }

                $blog = $manager->get($pages[1]);
                $response['blog'] = $manager->getNext($blog);
                break;

            case "header":
                $blog = $manager->get($pages[1]);
                $header = $headerManager->read($blog);

                header('Content-Type: image/jpeg');
                header('Expires: ' . date('r', time() + 864000));
                header("Pragma: public");
                header("Cache-Control: public");

                try {
                    echo $header->read();
                } catch (\Exception $e) {
                }

                exit;

            default:
                if (is_numeric($pages[0]) || Core\Luid::isValid($pages[0])) {
                    $blog = $manager->get($pages[0]);

                    if (
                        !$blog ||
                        Helpers\Flags::shouldFail($blog) ||
                        !Core\Security\ACL::_()->read($blog)
                        || ($blog->getTimeCreated() > time() && !$blog->canEdit())
                    ) {
                        break;
                    }

                    $response['blog'] = $blog;

                    if (!Core\Session::isLoggedIn()) {
                        $owner = Di::_()->get('EntitiesBuilder')->single($blog->owner_guid);
                        $response['require_login'] = !$this->checkBalance($owner);
                    }
                }
                break;
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        $manager = new Core\Blogs\Manager();
        $header = new Core\Blogs\Header();

        $response = [];
        $alreadyPublished = false;
        $oldAccessId = Access::UNKNOWN;

        $editing = isset($pages[0]) && (is_numeric($pages[0]) || Core\Luid::isValid($pages[0]));

        if ($editing) {
            $blog = $manager->get($pages[0]);

            $alreadyPublished = $blog->isPublished();
            $oldAccessId = $alreadyPublished ? $blog->getAccessId() : $blog->getDraftAccessId();
        } else {
            $blog = new Core\Blogs\Blog();
            $blog
                ->setOwnerObj(Core\Session::getLoggedinUser())
                ->setContainerGuid(Core\Session::getLoggedInUserGuid());

            $owner = Core\Session::getLoggedinUser();
            if ($owner->icontime == $owner->time_created) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Please ensure your channel has an avatar before creating a blog',
                ]);
            }
        }

        $captcha = Core\Di\Di::_()->get('Captcha\Manager');

        if (!isset($_POST['captcha'])) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Please fill out the captcha field',
            ]);
        }

        if (!$captcha->verifyFromClientJson($_POST['captcha'] ?? '')) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Please ensure that the captcha you entered is correct',
            ]);
        }

        if (!$this->checkBalance(Core\Session::getLoggedInUser()) && preg_match('/(\b(https?|ftp|file):\/\/[^\s\]]+)/im', $_POST['description'] ?? '')) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must have tokens in your OffChain or OnChain wallets to make a blog with hyperlinks',
            ]);
        }

        if (isset($_POST['title'])) {
            $blog->setTitle($_POST['title']);
        }

        if (isset($_POST['description'])) {
            $blog->setBody($_POST['description']);
        } elseif (isset($_POST['body'])) {
            $blog->setBody($_POST['body']);
        }

        if (isset($_POST['access_id'])) {
            $blog->setAccessId($_POST['access_id']);
        }

        if (isset($_POST['license'])) {
            $blog->setLicense($_POST['license']);
        }

        if (isset($_POST['category'])) {
            $blog->setCategories([ $_POST['category'] ]);
        } elseif (isset($_POST['categories'])) {
            $blog->setCategories($_POST['categories']);
        }

        if (isset($_POST['tags']) && $_POST['tags'] !== '') {
            $tags = !is_array($_POST['tags']) ? json_decode($_POST['tags']) : $_POST['tags'];
            $blog->setTags($tags);
        }

        if (isset($_POST['mature'])) {
            $blog->setMature(!!$_POST['mature']);
        }

        if (isset($_POST['nsfw'])) {
            $nsfw = !is_array($_POST['nsfw']) ? json_decode($_POST['nsfw']) : $_POST['nsfw'];
            $blog->setNsfw($nsfw);
        }

        if (isset($_POST['published'])) {
            $published = is_string($_POST['published']) ? json_decode($_POST['published']) : $_POST['published'];
            $blog->setPublished($published);
        }

        if (isset($_POST['monetized'])) {
            $blog->setMonetized(!!$_POST['monetized']);
        }

        if (isset($_POST['slug'])) {
            $blog->setSlug($_POST['slug']);
        }

        if (isset($_POST['custom_meta'])) {
            $meta = is_string($_POST['custom_meta']) ? json_decode($_POST['custom_meta'], true) : $_POST['custom_meta'];

            if (is_array($meta)) {
                $blog->setCustomMeta($meta);
            }
        }

        if (isset($_POST['editor_version'])) {
            $blog->setEditorVersion($_POST['editor_version']);
        }

        $blog->setLastSave(time());

        if (isset($_POST['wire_threshold'])) {
            $threshold = is_string($_POST['wire_threshold']) ? json_decode($_POST['wire_threshold'], true) : $_POST['wire_threshold'];
            $blog->setWireThreshold($threshold);
            $blog->markAsDirty('wireThreshold');
            $blog->markAsDirty('paywall');
            Di::_()->get('Wire\Paywall\Manager')->validateEntity($blog, true);
        }

        if ((isset($_POST['nsfw']) && $_POST['nsfw'])
            || (isset($_POST['mature']) && $_POST['mature'])) {
            $user = Core\Session::getLoggedInUser();

            if (!$user->getMatureContent()) {
                $user->setMatureContent(true);
                $user->save();
            }
        }


        if (isset($_POST['time_created'])) {
            try {
                $timeCreatedDelegate = new Core\Blogs\Delegates\TimeCreatedDelegate();

                if ($editing) {
                    $timeCreatedDelegate->onUpdate($blog, $_POST['time_created'], time());
                } else {
                    $timeCreatedDelegate->onAdd($blog, $_POST['time_created'], time());
                }
            } catch (\Exception $e) {
                return Factory::response([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (!$blog->isPublished()) {
            $blog->setAccessId(Access::UNLISTED);
            $blog->setDraftAccessId($_POST['access_id']);
        } elseif ($blog->getTimePublished() == '') {
            $blog->setTimePublished($blog->getTimeCreated() ?: time());
        }

        if (!$blog->canEdit()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Sorry, you do not have permission'
            ]);
        }

        if (!$blog->getBody()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Sorry, your blog must have some content'
            ]);
        }

        // This is a first create blog that should have a banner
        // We are trying to stop spam with this check
        if ($blog->isPublished() && !$editing && !is_uploaded_file($_FILES['file']['tmp_name'])) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must upload a banner'
            ]);
        }

        try {
            if ($editing) {
                $saved = $manager->update($blog);
            } else {
                $saved = $manager->add($blog);
            }
        } catch (UnverifiedEmailException $e) {
            throw $e;
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        if ($saved && is_uploaded_file($_FILES['file']['tmp_name'])) {
            /** @var Core\Media\Imagick\Manager $manager */
            $manager = Core\Di\Di::_()->get('Media\Imagick\Manager');

            try {
                $manager->setImage($_FILES['file']['tmp_name'])
                    ->resize(2000, 1000);

                $header->write($blog, $manager->getJpeg(), isset($_POST['header_top']) ? (int)$_POST['header_top'] : 0);
            } catch (\ImagickException $e) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Invalid image file',
                ]);
            }
        }

        if ($saved) {
            if ($blog->isPublished() && in_array($blog->getAccessId(), [Access::PUBLIC, Access::LOGGED_IN], false)) {
                if (!$editing || ($editing && !$alreadyPublished) || ($editing && $oldAccessId == Access::UNLISTED)) {
                    (new CreateActivity())->save($blog);
                }
            }

            $response['guid'] = (string) $blog->getGuid();
            $response['slug'] = $blog->getSlug();
            $response['route'] = $blog->getUrl(true);
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
        Factory::isLoggedIn();

        $manager = new Core\Blogs\Manager();
        $header = new Core\Blogs\Header();

        if (isset($pages[0]) && is_numeric($pages[0])) {
            $blog = $manager->get($pages[0]);
        } else {
            $blog = new Core\Blogs\Blog();
        }

        if (is_uploaded_file($_FILES['header']['tmp_name'])) {
            $manager->setImage($_FILES['header']['tmp_name'])
                ->resize(2000, 1000);

            $header->write($blog, $manager->getJpeg(), isset($_POST['header_top']) ? (int) $_POST['header_top'] : 0);
        }

        return Factory::response([]);
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        $manager = new Core\Blogs\Manager();

        $blog = $manager->get($pages[0]);

        if ($blog && $blog->canEdit()) {
            $manager->delete($blog);
        }

        return Factory::response([]);
    }

    /**
     * Checks the balance
     * @param User $user
     * @return bool
     */
    private function checkBalance(User $user): bool
    {
        return Di::_()->get('Blockchain\Wallets\Balance')
            ->setUser($user)
            ->get()
            ->div(10 ** 18)
            ->toDouble() > 0;
    }
}
