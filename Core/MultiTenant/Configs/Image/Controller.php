<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Image;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private Manager $manager,
        private Config $config
    ) {
    }

    public function get(ServerRequestInterface $request)
    {
        if (!$type = MultiTenantConfigImageType::tryFrom($request->getAttribute("parameters")["imageType"])) {
            throw new UserErrorException('A valid type must be provided');
        }

        $file = $this->manager->getImageFileByType($type);
        $file->open('read');
        $contents = $this->manager->getImageContentsFromFile($file);

        if (empty($contents)) {
            exit;
        }

        header('Content-Type: ' . $file->getMimeType($contents));
        header('Expires: ' . date('r', time() + 864000));
        header("Pragma: public");
        header("Cache-Control: public");

        echo $contents;
        exit;
    }


    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function upload(ServerRequest $request): JsonResponse
    {
        if (!$type = MultiTenantConfigImageType::tryFrom($_POST['type'])) {
            throw new UserErrorException('A valid type must be provided');
        }

        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $this->manager->upload($_FILES['file']['tmp_name'], $type);
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
