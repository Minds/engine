<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use ImagickException;
use Minds\Common\IpAddress;
use Minds\Core\Di\Di;
use Minds\Core\Verification\Exceptions\VerificationRequestDeviceTypeNotFoundException;
use Minds\Core\Verification\Exceptions\VerificationRequestExpiredException;
use Minds\Core\Verification\Exceptions\VerificationRequestFailedException;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Models\VerificationRequestDeviceType;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
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
     * @throws UserErrorException
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

        if (!$deviceTypeId) {
            throw new UserErrorException('device_id must be provided in query params', 400);
        }

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
     * @throws UserErrorException
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

        ['device_type' => $deviceTypeId, 'device_token' => $deviceToken] = $request->getParsedBody();

        if (!$deviceTypeId) {
            throw new UserErrorException('device_id must be provided in body', 400);
        }

        if (!$deviceToken) {
            throw new UserErrorException('device_token must be provided in body', 400);
        }

        $ipAddr = (new IpAddress())->setServerRequest($request)->get();

        $verificationRequest = $this->manager
            ->setUser($loggedInUser)
            ->createVerificationRequest(
                deviceId: VerificationRequestDeviceType::fromId($deviceTypeId) . ":" . $deviceId,
                deviceToken: $deviceToken,
                ipAddr: $ipAddr
            );

        return new JsonResponse($verificationRequest->export(), 201);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ImagickException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws VerificationRequestDeviceTypeNotFoundException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestFailedException
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
            'device_type' => $deviceTypeId,
            'geo' => $geo,
        ] = $request->getParsedBody();

        if (!$deviceTypeId) {
            throw new UserErrorException('device_id must be provided in body', 400);
        }

        $ipAddr = (new IpAddress())->setServerRequest($request)->get();

        $this->manager
            ->setUser($loggedInUser)
            ->verifyAccount(
                deviceId: VerificationRequestDeviceType::fromId($deviceTypeId) . ":" . $deviceId,
                ipAddr: $ipAddr,
                imageStream: $image->getStream(),
                sensorData: $sensorData,
                geo: $geo
            );

        return new JsonResponse("", 201);
    }
}
