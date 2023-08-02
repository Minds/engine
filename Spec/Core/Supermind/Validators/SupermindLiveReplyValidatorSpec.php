<?php

namespace Spec\Minds\Core\Supermind\Validators;

use Exception;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Core\Supermind\Validators\SupermindLiveReplyValidator;
use Minds\Entities\User;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;

class SupermindLiveReplyValidatorSpec extends ObjectBehavior
{
    /** @var SupermindManager */
    private $supermindManager;

    /** @var Logger */
    private $logger;

    public function let(
        SupermindManager $supermindManager,
        Logger $logger
    ) {
        $this->supermindManager = $supermindManager;
        $this->logger = $logger;
        $this->beConstructedWith($supermindManager, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindLiveReplyValidator::class);
    }

    public function it_should_validate_a_valid_change(
        ServerRequestInterface $request,
        User $user,
        SupermindRequest $supermindRequest
    ) {
        $guid = '1234567890123456';
        $userGuid = '2234567890123456';
        $supermindRequestRecieverGuid = '2234567890123456';

        $request->getAttribute("_user")
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute("parameters")
            ->shouldBeCalled()
            ->willReturn(["guid" => $guid]);

        $supermindRequest->getReplyType()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestReplyType::LIVE);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestRecieverGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->getRequest($guid)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->validate($request)->shouldBe(true);
        $this->getErrors()->count()->shouldBe(0);
    }

    public function it_should_NOT_validate_a_change_when_no_guid_is_provided(
        ServerRequestInterface $request,
        User $user
    ) {
        $guid = null;

        $request->getAttribute("_user")
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute("parameters")
            ->shouldBeCalled()
            ->willReturn(["guid" => $guid]);

        $this->validate($request)->shouldBe(false);
        $this->getErrors()->shouldBeLike((new ValidationErrorCollection())->add(
            new ValidationError(
                'guid',
                'You must supply a Supermind request guid'
            )
        ));
    }

    public function it_should_NOT_validate_a_change_when_user_is_forbidden_from_reading_supermind(
        ServerRequestInterface $request,
        User $user,
    ) {
        $guid = '1234567890123456';

        $request->getAttribute("_user")
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute("parameters")
            ->shouldBeCalled()
            ->willReturn(["guid" => $guid]);

        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->getRequest($guid)
            ->shouldBeCalled()
            ->willThrow(new ForbiddenException());

        $this->validate($request)->shouldBe(false);
        $this->getErrors()->shouldBeLike((new ValidationErrorCollection())->add(
            new ValidationError(
                'guid',
                'You are not allowed to interact with this Supermind request'
            )
        ));
    }

    public function it_should_NOT_validate_a_change_when_a_general_exception_is_thrown_whilst_reading_a_supermind(
        ServerRequestInterface $request,
        User $user,
    ) {
        $guid = '1234567890123456';

        $request->getAttribute("_user")
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute("parameters")
            ->shouldBeCalled()
            ->willReturn(["guid" => $guid]);

        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->getRequest($guid)
            ->shouldBeCalled()
            ->willThrow(new Exception());

        $this->validate($request)->shouldBe(false);
        $this->getErrors()->shouldBeLike((new ValidationErrorCollection())->add(
            new ValidationError(
                'guid',
                'An unknown error has occurred whilst interacting with this Supermind request'
            )
        ));
    }

    public function it_should_NOT_validate_a_change_when_reply_type_is_NOT_live(
        ServerRequestInterface $request,
        User $user,
        SupermindRequest $supermindRequest
    ) {
        $guid = '1234567890123456';
        $userGuid = '2234567890123456';
        $supermindRequestRecieverGuid = '2234567890123456';

        $request->getAttribute("_user")
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute("parameters")
            ->shouldBeCalled()
            ->willReturn(["guid" => $guid]);

        $supermindRequest->getReplyType()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestReplyType::TEXT);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestRecieverGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->getRequest($guid)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->validate($request)->shouldBe(false);
        $this->getErrors()->shouldBeLike((new ValidationErrorCollection())->add(
            new ValidationError(
                'guid',
                'This Supermind request cannot be accepted as a live reply'
            )
        ));
    }

    public function it_should_NOT_validate_a_change_when_receiver_is_not_logged_in_user(
        ServerRequestInterface $request,
        User $user,
        SupermindRequest $supermindRequest
    ) {
        $guid = '1234567890123456';
        $userGuid = '2234567890123456';
        $supermindRequestRecieverGuid = '3234567890123456';

        $request->getAttribute("_user")
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute("parameters")
            ->shouldBeCalled()
            ->willReturn(["guid" => $guid]);

        $supermindRequest->getReplyType()
            ->shouldBeCalled()
            ->willReturn(SupermindRequestReplyType::LIVE);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestRecieverGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->getRequest($guid)
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->validate($request)->shouldBe(false);
        $this->getErrors()->shouldBeLike((new ValidationErrorCollection())->add(
            new ValidationError(
                'guid',
                'You are not the intended recipient for this Supermind request'
            )
        ));
    }
}
