<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use Exception;
use ImagickException;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Core\Notifications\Push\Services\PushServiceInterface;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Verification\Exceptions\UserVerificationPushNotificationFailedException;
use Minds\Core\Verification\Exceptions\VerificationCodeMismatchException;
use Minds\Core\Verification\Exceptions\VerificationRequestExpiredException;
use Minds\Core\Verification\Exceptions\VerificationRequestFailedException;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Helpers\ImageProcessor;
use Minds\Core\Verification\Helpers\OCR\MindsOCRInterface;
use Minds\Core\Verification\Models\VerificationRequest;
use Minds\Core\Verification\Models\VerificationRequestDeviceType;
use Minds\Core\Verification\Models\VerificationRequestStatus;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Minds\Traits\RandomGenerators;
use Zend\Diactoros\Stream;

class Manager
{
    use RandomGenerators;

    private User $user;

    public function __construct(
        private ?Repository $repository = null,
        private ?MindsOCRInterface $ocrClient = null,
        private ?ImageProcessor $imageProcessor = null,
        private ?FcmService $fcmService = null,
        private ?ApnsService $apnsService = null,
        private ?Logger $logger = null
    ) {
        $this->repository ??= Di::_()->get('Verification\Repository');
        $this->ocrClient ??= Di::_()->get('Verification\Helpers\OCR\DefaultOCRClient');
        $this->imageProcessor ??= new ImageProcessor();

        // Push Notification services
        $this->fcmService ??= Di::_()->get(FcmService::class);
        $this->apnsService ??= Di::_()->get(ApnsService::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $deviceId
     * @return VerificationRequest
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     */
    public function getVerificationRequest(string $deviceId): VerificationRequest
    {
        return $this->repository->getVerificationRequestDetails(
            userGuid: $this->user->getGuid(),
            deviceId: $deviceId
        );
    }

    /**
     * Get verification request by user set in state.
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     * @return VerificationRequest
     */
    public function getVerificationRequestByUser(): VerificationRequest
    {
        if (!$this->user) {
            throw new ServerErrorException('Could not get verification request for null user');
        }
        return $this->repository->getVerificationRequestDetailsByUserGuid(
            userGuid: (string) $this->user->getGuid()
        );
    }

    /**
     * Whether instance user is verified.
     * @return bool true if instance user is verified.
     */
    public function isVerified(): bool
    {
        try {
            $verificationRequest = $this->getVerificationRequestByUser();
            return $verificationRequest->isVerified();
        } catch (VerificationRequestNotFoundException $e) {
            return false;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * @param string $deviceId
     * @param string $deviceToken
     * @param string $ipAddr
     * @return VerificationRequest
     * @throws ServerErrorException
     * @throws UserVerificationPushNotificationFailedException
     */
    public function createVerificationRequest(string $deviceId, string $deviceToken, string $ipAddr): VerificationRequest
    {
        try {
            $verificationRequest = $this->getVerificationRequest($deviceId);
            if ($verificationRequest->isExpired()) {
                throw new VerificationRequestExpiredException();
            }

            $this->sendRequestPushNotification($verificationRequest);

            return $verificationRequest;
        } catch (VerificationRequestNotFoundException|VerificationRequestExpiredException $e) {
            $verificationRequest = new VerificationRequest();
            $verificationRequest
                ->setUserGuid($this->user->getGuid())
                ->setDeviceId($deviceId)
                ->setDeviceToken($deviceToken)
                ->setVerificationCode($this->generateRandomInteger())
                ->setIpAddr($ipAddr);
        }

        if (!$this->repository->createVerificationRequest($verificationRequest)) {
            throw new ServerErrorException("An error occurred whilst creating the verification request");
        }

        // Send push notification to the device
        $this->sendRequestPushNotification($verificationRequest);

        return $verificationRequest;
    }

    /**
     * @param string $deviceId
     * @param string $ipAddr
     * @param Stream $imageStream
     * @param string $sensorData
     * @param string $geo
     * @return bool
     * @throws ImagickException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestFailedException
     * @throws VerificationRequestNotFoundException
     */
    public function verifyAccount(
        string $deviceId,
        string $ipAddr,
        Stream $imageStream,
        string $sensorData,
        string $geo,
    ): bool {
        $verificationRequest = $this->repository->getVerificationRequestDetails(
            userGuid: $this->user->getGuid(),
            deviceId: $deviceId
        );

        if ($verificationRequest->isExpired()) {
            $this->repository->updateVerificationRequestStatus(
                verificationRequest: $verificationRequest,
                status: VerificationRequestStatus::EXPIRED
            );
            throw new VerificationRequestExpiredException();
        }

        if ($verificationRequest->getIpAddr() !== $ipAddr) {
            throw new UserErrorException("We detected a different IP address between your request and response. Please request a new code");
        }

        // TODO Store the raw image on S3

        $imageProcessor = $this->imageProcessor->withStream($imageStream);

        $imageProcessor->cropVerificationImage();

        $providedCode = $this->ocrClient->processImageScan($imageProcessor->getImageAsString());

        if ($verificationRequest->getVerificationCode() !== $providedCode) {
            $this->repository->updateVerificationRequestStatus(
                verificationRequest: $verificationRequest,
                status: VerificationRequestStatus::FAILED
            );
            throw new VerificationCodeMismatchException();
        }

        $this->repository->markRequestAsVerified(
            verificationRequest: $verificationRequest,
            geo: $geo,
            sensorData: $sensorData,
        );
        return true;
    }

    /**
     * @param VerificationRequest $request
     * @return bool
     * @throws UserVerificationPushNotificationFailedException]
     * @throws Exception
     */
    private function sendRequestPushNotification(VerificationRequest $request): bool
    {
        [$deviceType] = explode(':', $request->getDeviceId());

        $notification = (new CustomPushNotification())
            ->setTitle("Minds User Verification")
            ->setBody("Your verification code is {$request->getVerificationCode()}")
            ->setUri('')
            ->setMetadata([
                'verification_code' => $request->getVerificationCode()
            ])
            ->setDeviceSubscription(
                (new DeviceSubscription())
                ->setUserGuid($request->getUserGuid())
                ->setService(VerificationRequestDeviceType::PUSH_NOTIFICATION_SERVICE_MAPPING[$deviceType])
                ->setToken($request->getDeviceToken())
            );

        $pushNotificationService = $this->getPushNotificationService((int) $deviceType);

        if (!$pushNotificationService->send($notification)) {
            throw new UserVerificationPushNotificationFailedException();
        }

        return true;
    }

    /**
     * @param int $deviceType
     * @return PushServiceInterface
     * @throws Exception
     */
    private function getPushNotificationService(int $deviceType): PushServiceInterface
    {
        return match ($deviceType) {
            VerificationRequestDeviceType::ANDROID => $this->fcmService,
            VerificationRequestDeviceType::IOS => $this->apnsService,
            default => throw new Exception('Invalid device type')
        };
    }
}
