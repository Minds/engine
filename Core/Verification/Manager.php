<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use ImagickException;
use Minds\Core\Di\Di;
use Minds\Core\Verification\Exceptions\VerificationRequestExpiredException;
use Minds\Core\Verification\Exceptions\VerificationRequestFailedException;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Helpers\ImageProcessor;
use Minds\Core\Verification\Helpers\OCR\MindsOCRInterface;
use Minds\Core\Verification\Models\VerificationRequest;
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
        private ?ImageProcessor $imageProcessor = null
    ) {
        $this->repository ??= Di::_()->get('Verification\Repository');
        $this->ocrClient ??= Di::_()->get('Verification\Helpers\OCR\DefaultOCRClient');
        $this->imageProcessor ??= new ImageProcessor();
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
     * @param string $deviceId
     * @param string $ipAddr
     * @return VerificationRequest
     * @throws ServerErrorException
     */
    public function createVerificationRequest(string $deviceId, string $ipAddr): VerificationRequest
    {
        $verificationRequest = new VerificationRequest();
        try {
            $verificationRequest = $this->getVerificationRequest($deviceId);
            if ($verificationRequest->isExpired()) {
                throw new VerificationRequestExpiredException();
            }

            return $verificationRequest;
        } catch (VerificationRequestNotFoundException|VerificationRequestExpiredException $e) {
            $verificationRequest = new VerificationRequest();
            $verificationRequest
                ->setUserGuid($this->user->getGuid())
                ->setDeviceId($deviceId)
                ->setVerificationCode($this->generateRandomInteger())
                ->setIpAddr($ipAddr);
        }

        if (!$this->repository->createVerificationRequest($verificationRequest)) {
            throw new ServerErrorException("An error occurred whilst creating the verification request");
        }

        // Send push notification to the device

        return $verificationRequest;
    }

    /**
     * @param string $deviceId
     * @param string $ipAddr
     * @param Stream $imageStream
     * @param string $sensorData
     * @return bool
     * @throws ImagickException
     * @throws ServerErrorException
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
                $verificationRequest,
                status: VerificationRequestStatus::FAILED
            );
            throw new VerificationRequestFailedException();
        }

        $this->repository->markRequestAsVerified(
            $verificationRequest,
            sensorData: $sensorData,
            geo: $geo,
        );
        return true;
    }
}
