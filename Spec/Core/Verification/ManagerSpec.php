<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Verification;

use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Verification\Exceptions\UserVerificationPushNotificationFailedException;
use Minds\Core\Verification\Exceptions\VerificationCodeMismatchException;
use Minds\Core\Verification\Exceptions\VerificationRequestExpiredException;
use Minds\Core\Verification\Exceptions\VerificationRequestFailedException;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Helpers\ImageProcessor;
use Minds\Core\Verification\Helpers\OCR\MindsOCRInterface;
use Minds\Core\Verification\Manager;
use Minds\Core\Verification\Models\VerificationRequest;
use Minds\Core\Verification\Repository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Stream;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $ocrClient;
    private Collaborator $imageProcessor;
    private Collaborator $fcmService;
    private Collaborator $apnsService;
    private Collaborator $logger;

    public function let(
        Repository $repository,
        MindsOCRInterface $ocrClient,
        ImageProcessor $imageProcessor,
        FcmService $fcmService,
        ApnsService $apnsService,
        Logger $logger
    ) {
        $this->repository = $repository;
        $this->ocrClient = $ocrClient;
        $this->imageProcessor = $imageProcessor;
        $this->fcmService = $fcmService;
        $this->apnsService = $apnsService;
        $this->logger = $logger;

        $this->beConstructedWith(
            $this->repository,
            $this->ocrClient,
            $this->imageProcessor,
            $this->fcmService,
            $this->apnsService,
            $this->logger
        );
    }

    public function it_is_initializable(): void
    {
        $this->beAnInstanceOf(Manager::class);
    }

    /**
     * @param User $user
     * @return void
     * @throws VerificationRequestNotFoundException
     * @throws ServerErrorException
     */
    public function it_successfully_gets_verification_request(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetails(
            '123',
            '123'
        )
            ->willReturn(new VerificationRequest());

        $this->getVerificationRequest('123')
            ->shouldBeAnInstanceOf(VerificationRequest::class);
    }

    /**
     * @param User $user
     * @return void
     * @throws VerificationRequestNotFoundException
     * @throws ServerErrorException
     */
    public function it_should_get_verification_request_and_throw_exception_when_no_request_is_found(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetails(
            '123',
            '123'
        )
            ->willThrow(new VerificationRequestNotFoundException());

        $this->shouldThrow(VerificationRequestNotFoundException::class)->during('getVerificationRequest', ['123']);
    }

    public function it_successfully_gets_verification_request_by_user(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetailsByUserGuid(
            '123'
        )
            ->willReturn(new VerificationRequest());

        $this->getVerificationRequestByUser()
            ->shouldBeAnInstanceOf(VerificationRequest::class);
    }

    public function it_successfully_gets_verification_request_by_user_and_throw_exception_when_no_request_is_found(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetailsByUserGuid(
            '123'
        )
            ->willThrow(new VerificationRequestNotFoundException());

        $this->shouldThrow(VerificationRequestNotFoundException::class)->during('getVerificationRequestByUser', []);
    }

    public function it_checks_a_user_is_verified_successfully(
        User $user,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isVerified()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->getVerificationRequestDetailsByUserGuid(
            '123'
        )
            ->willReturn($verificationRequest);

        $this->isVerified()->shouldBe(true);
    }

    public function it_checks_a_user_is_not_verified_fully(
        User $user,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isVerified()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->repository->getVerificationRequestDetailsByUserGuid(
            '123'
        )
            ->willReturn($verificationRequest);

        $this->isVerified()->shouldBe(false);
    }

    public function it_checks_a_user_is_has_no_verification_request_pending(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetailsByUserGuid(
            '123'
        )
            ->willThrow(new VerificationRequestNotFoundException());

        $this->isVerified()->shouldBe(false);
    }

    public function it_represents_a_user_as_unverified_on_other_exception_on_check(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetailsByUserGuid(
            '123'
        )
            ->willThrow(new ServerErrorException());

        $this->isVerified()->shouldBe(false);
    }

    public function it_should_create_verification_request(
        User $user
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetails(
            Argument::type('string'),
            Argument::type('string'),
        )
            ->shouldBeCalledOnce()
            ->willThrow(new VerificationRequestNotFoundException());

        $this->repository->createVerificationRequest(Argument::type(VerificationRequest::class))
            ->willReturn(true);

        $this->fcmService->send(Argument::type(CustomPushNotification::class))
            ->willReturn(true);

        $this->createVerificationRequest('1:123', '123', '')
            ->shouldBeAnInstanceOf(VerificationRequest::class);
    }

    public function it_should_try_to_create_verification_request_when_existing_request_is_expired(
        User $user,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isExpired()
            ->willReturn(true);

        $this->repository->getVerificationRequestDetails(
            Argument::type('string'),
            Argument::type('string'),
        )
            ->shouldBeCalledOnce()
            ->willReturn($verificationRequest);

        $this->repository->createVerificationRequest(Argument::type(VerificationRequest::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->fcmService->send(Argument::type(CustomPushNotification::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->createVerificationRequest('1:123', '123', '')
            ->shouldBeAnInstanceOf(VerificationRequest::class);
    }

    public function it_should_try_to_create_verification_request_and_request_exists_and_throw_push_notification_failed_exception(
        User $user,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isExpired()
            ->willReturn(false);

        $verificationRequest->getUserGuid()
            ->willReturn('123');

        $verificationRequest->getDeviceId()
            ->willReturn('1:123');

        $verificationRequest->getDeviceToken()
            ->willReturn('123');

        $verificationRequest->getVerificationCode()
            ->willReturn('123');

        $this->repository->getVerificationRequestDetails(
            Argument::type('string'),
            Argument::type('string'),
        )
            ->shouldBeCalledOnce()
            ->willReturn($verificationRequest);

        $this->repository->createVerificationRequest(Argument::type(VerificationRequest::class))
            ->willReturn(true);

        $this->fcmService->send(Argument::type(CustomPushNotification::class))
            ->willReturn(false);

        $this->shouldThrow(UserVerificationPushNotificationFailedException::class)->during('createVerificationRequest', ['123', '123', '']);
    }

    public function it_should_try_to_create_verification_request_and_request_doesnt_exists_and_throw_push_notification_failed_exception(
        User $user,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetails(
            Argument::type('string'),
            Argument::type('string'),
        )
            ->shouldBeCalledOnce()
            ->willThrow(new VerificationRequestNotFoundException());

        $this->repository->createVerificationRequest(Argument::type(VerificationRequest::class))
            ->willReturn(true);

        $this->fcmService->send(Argument::type(CustomPushNotification::class))
            ->willReturn(false);

        $this->shouldThrow(UserVerificationPushNotificationFailedException::class)->during('createVerificationRequest', ['1:123', '123', '']);
    }

    /**
     * @param User $user
     * @param Stream $imageStream
     * @param VerificationRequest $verificationRequest
     * @return void
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     * @throws \ImagickException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestFailedException
     * @throws UserErrorException
     */
    public function it_should_verify_user(
        User $user,
        Stream $imageStream,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isExpired()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $verificationRequest->getIpAddr()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getVerificationCode()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->repository->getVerificationRequestDetails(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($verificationRequest);

        $this->imageProcessor->withStream($imageStream)
            ->shouldBeCalledOnce()
            ->willReturn($this->imageProcessor);

        $this->imageProcessor->cropVerificationImage()
            ->shouldBeCalledOnce();

        $this->imageProcessor->getImageAsString()
            ->shouldBeCalledOnce()
            ->willReturn('');

        $this->ocrClient->processImageScan(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->repository->markRequestAsVerified(
            Argument::type(VerificationRequest::class),
            Argument::type('string'),
            Argument::type('string')
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->verifyAccount(
            deviceId: '123',
            ipAddr: '123',
            imageStream: $imageStream,
            sensorData: '',
            geo: '0,0'
        )
            ->shouldBeEqualTo(true);
    }

    /**
     * @param User $user
     * @param Stream $imageStream
     * @param VerificationRequest $verificationRequest
     * @return void
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     * @throws \ImagickException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestFailedException
     * @throws UserErrorException
     */
    public function it_should_try_to_verify_user_with_expired_request_and_throw_expired_request_exception(
        User $user,
        Stream $imageStream,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isExpired()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repository->getVerificationRequestDetails(
            Argument::type('string'),
            Argument::type('string')
        )
            ->shouldBeCalledOnce()
            ->willReturn($verificationRequest);

        $this->repository->updateVerificationRequestStatus(
            Argument::type(VerificationRequest::class),
            Argument::type('integer')
        )
            ->shouldBeCalledOnce();

        $this->shouldThrow(VerificationRequestExpiredException::class)->during(
            'verifyAccount',
            [
                '123',
                '123',
                $imageStream,
                '',
                '0,0'
            ]
        );
    }

    /**
     * @param User $user
     * @param Stream $imageStream
     * @return void
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     * @throws \ImagickException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestFailedException
     * @throws UserErrorException
     */
    public function it_should_try_to_verify_user_with_request_not_found_and_throw_request_not_found_exception(
        User $user,
        Stream $imageStream
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository->getVerificationRequestDetails(
            Argument::type('string'),
            Argument::type('string')
        )
            ->shouldBeCalledOnce()
            ->willThrow(new VerificationRequestNotFoundException());

        $this->shouldThrow(VerificationRequestNotFoundException::class)->during(
            'verifyAccount',
            [
                '123',
                '123',
                $imageStream,
                '',
                '0,0'
            ]
        );
    }

    /**
     * @param User $user
     * @param Stream $imageStream
     * @param VerificationRequest $verificationRequest
     * @return void
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     * @throws \ImagickException
     * @throws VerificationRequestExpiredException
     * @throws VerificationRequestFailedException
     * @throws UserErrorException
     */
    public function it_should_try_to_verify_user_with_mismatching_code_and_throw_verification_code_mismatch_exception(
        User $user,
        Stream $imageStream,
        VerificationRequest $verificationRequest
    ): void {
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $verificationRequest->isExpired()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $verificationRequest->getIpAddr()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getVerificationCode()
            ->shouldBeCalledOnce()
            ->willReturn('124');

        $this->repository->getVerificationRequestDetails(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($verificationRequest);

        $this->imageProcessor->withStream($imageStream)
            ->shouldBeCalledOnce()
            ->willReturn($this->imageProcessor);

        $this->imageProcessor->cropVerificationImage()
            ->shouldBeCalledOnce();

        $this->imageProcessor->getImageAsString()
            ->shouldBeCalledOnce()
            ->willReturn('');

        $this->ocrClient->processImageScan(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->repository->updateVerificationRequestStatus(
            Argument::type(VerificationRequest::class),
            Argument::type('integer')
        )
            ->shouldBeCalledOnce();

        $this->shouldThrow(VerificationCodeMismatchException::class)->during(
            'verifyAccount',
            [
                '123',
                '123',
                $imageStream,
                '',
                '0,0'
            ]
        );
    }
}
