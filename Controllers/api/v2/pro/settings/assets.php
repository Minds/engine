<?php
/**
 * settings assets
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\pro\settings;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Manager;
use Minds\Core\Pro\Assets\Manager as AssetsManager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;
use Zend\Diactoros\ServerRequest;

class assets implements Interfaces\Api
{
    /** @var ServerRequest */
    public $request;
    
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function post($pages)
    {
        $type = $pages[0] ?? null;

        // Check and validate user

        $user = Session::getLoggedinUser();

        if (isset($pages[1]) && $pages[1]) {
            if (!Session::isAdmin()) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You are not authorized',
                ]);
            }

            $user = new User($pages[1]);
        }

        // Check uploaded file

        /** @var \Zend\Diactoros\UploadedFile[] $files */
        $files = $this->request->getUploadedFiles();

        if (!$files || !isset($files['file'])) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Missing file',
            ]);
        }

        $file = $files['file'];

        if ($file->getError()) {
            return Factory::response([
                'status' => 'error',
                'message' => sprintf('Error %s when uploading file', $files['file']->getError()),
            ]);
        }

        // Get Pro managers

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager
            ->setUser($user)
            ->setActor(Session::getLoggedinUser());

        if (!$manager->isActive()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not Pro',
            ]);
        }

        /** @var AssetsManager $assetsManager */
        $assetsManager = Di::_()->get('Pro\Assets\Manager');
        $assetsManager
            ->setType($type)
            ->setUser($user)
            ->setActor(Session::getLoggedinUser());

        try {
            $success = $assetsManager
                ->set($file);

            if (!$success) {
                throw new Exception(sprintf("Cannot save Pro %s asset", $type));
            }
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
