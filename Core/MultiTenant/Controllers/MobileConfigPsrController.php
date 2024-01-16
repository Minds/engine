<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Exception;
use ImagickException;
use InvalidParameterException;
use IOException;
use Minds\Core\MultiTenant\Enums\MobileConfigImageTypeEnum;
use Minds\Core\MultiTenant\Services\MobileConfigAssetsService;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class MobileConfigPsrController
{
    public function __construct(
        private readonly MobileConfigAssetsService $mobileConfigAssetsService
    ) {
    }

    /**
     * Get an image for the passed in type.
     * @param ServerRequestInterface $request - server request.
     * @return void
     * @throws UserErrorException
     * @throws IOException
     * @throws InvalidParameterException
     * @throws Exception
     */
    public function get(ServerRequestInterface $request): void
    {
        if (!$type = MobileConfigImageTypeEnum::tryFrom($request->getAttribute("parameters")["imageType"])) {
            throw new UserErrorException('A valid type must be provided');
        }

        $file = $this->mobileConfigAssetsService->getImageFileByType($type);
        $file->open('read');

        $contents = $this->mobileConfigAssetsService->getImageContentsFromFile($type, $file);

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
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws IOException
     * @throws InvalidParameterException
     * @throws UserErrorException
     * @throws ImagickException
     */
    public function upload(ServerRequestInterface $request): JsonResponse
    {
        if (!($type = MobileConfigImageTypeEnum::tryFrom($_POST['type']))) {
            throw new UserErrorException('A valid type must be provided');
        }

        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $this->mobileConfigAssetsService->upload($type, $_FILES['file']['tmp_name']);
        }

        return new JsonResponse([
            'success' => true
        ]);
    }
}
