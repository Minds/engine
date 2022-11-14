<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use ImagickException;
use Minds\Core\Di\Di;
use Minds\Core\Verification\Exceptions\VerificationRequestDeviceTypeNotFoundException;
use Minds\Core\Verification\Exceptions\VerificationRequestExpiredException;
use Minds\Core\Verification\Exceptions\VerificationRequestFailedException;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Models\VerificationRequestDeviceType;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\UploadedFile;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Verification\Manager');
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ServerErrorException
     * @throws VerificationRequestDeviceTypeNotFoundException
     * @throws VerificationRequestNotFoundException
     */
    // #[OA\Get(
    //     path: '/api/v3/verification/:device_id',
    //     parameters: [
    //        new OA\Parameter(
    //            name: "device_id",
    //            in: "path",
    //            required: true,
    //            schema: new OA\Schema(type: 'string')
    //        )
    //     ],
    //     responses: [
    //        new OA\Response(response: 200, description: "Ok"),
    //        new OA\Response(response: 400, description: "Bad Request"),
    //        new OA\Response(response: 500, description: "Internal Server Error")
    //     ]
    // )]
    public function getVerificationStatus(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $deviceId = $request->getAttribute("parameters")["deviceid"];
        ['device_type' => $deviceTypeId] = $request->getQueryParams();

        $verificationRequest = $this->manager
            ->setUser($loggedInUser)
            ->getVerificationRequest(
                VerificationRequestDeviceType::fromId($deviceTypeId) . ":" . $deviceId
            );

        return new JsonResponse(
            $verificationRequest->export()
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ServerErrorException
     * @throws VerificationRequestDeviceTypeNotFoundException
     */
    // #[OA\Post(
    //     path: '/api/v3/verification/:device_id',
    //     parameters: [
    //        new OA\Parameter(
    //            name: "device_id",
    //            in: "path",
    //            required: true,
    //            schema: new OA\Schema(type: 'string')
    //        )
    //     ],
    //     responses: [
    //        new OA\Response(response: 201, description: "Resource Created"),
    //        new OA\Response(response: 500, description: "Internal Server Error")
    //     ]
    // )]
    public function generateVerificationCode(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $deviceId = $request->getAttribute("parameters")["deviceid"];

        ['device_type' => $deviceTypeId] = $request->getParsedBody();

        $verificationRequest = $this->manager
            ->setUser($loggedInUser)
            ->createVerificationRequest(
                VerificationRequestDeviceType::fromId($deviceTypeId) . ":" . $deviceId
            );

        return new JsonResponse($verificationRequest->export(), 201);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws VerificationRequestFailedException
     * @throws ImagickException
     * @throws ServerErrorException
     * @throws VerificationRequestDeviceTypeNotFoundException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestNotFoundException
     */
    // #[OA\Post(
    //     path: '/api/v3/verification/:device_id/verify',
    //     parameters: [
    //        new OA\Parameter(
    //            name: "device_id",
    //            in: "path",
    //            required: true,
    //            schema: new OA\Schema(type: 'string')
    //        )
    //     ],
    //     responses: [
    //        new OA\Response(response: 201, description: "Resource Created"),
    //        new OA\Response(response: 400, description: "Bad Request"),
    //        new OA\Response(response: 500, description: "Internal Server Error")
    //     ]
    // )]
    public function verifyAccount(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $deviceId = $request->getAttribute("parameters")["deviceid"];

        /**
         * @var UploadedFile $image
         */
        ['image' => $image] = $request->getUploadedFiles();

        [
            'sensor_data' => $sensorData,
            'device_type' => $deviceTypeId
        ] = $request->getParsedBody();

        $this->manager
            ->setUser($loggedInUser)
            ->verifyAccount([
                'imageStream' => $image->getStream(),
                'deviceId' => VerificationRequestDeviceType::fromId($deviceTypeId) . ":" . $deviceId,
                'sensorData' => $sensorData
            ]);

        return new JsonResponse("", 201);
    }
}
