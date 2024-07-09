<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Controllers\SiteMembershipBatchPsrController;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipBatchService;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

class SiteMembershipBatchPsrControllerSpec extends ObjectBehavior
{
    private Collaborator $batchServiceMock;

    public function let(SiteMembershipBatchService $batchServiceMock)
    {
        $this->beConstructedWith($batchServiceMock);
        $this->batchServiceMock = $batchServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SiteMembershipBatchPsrController::class);
    }

    public function it_should_throw_error_if_not_array_passed(ServerRequestInterface $requestMock)
    {
        $requestMock->getParsedBody()
            ->willReturn('foobar');
        $this->shouldThrow(UserErrorException::class)->duringOnBatchRequest($requestMock);
    }

    public function it_should_throw_error_if_array_is_too_large(ServerRequestInterface $requestMock)
    {
        $requestMock->getParsedBody()
            ->willReturn(array_fill(0, 501, []));
        $this->shouldThrow(UserErrorException::class)->duringOnBatchRequest($requestMock);
    }

    public function it_should_throw_error_if_invalid_id_type_provided(ServerRequestInterface $requestMock)
    {
        $requestMock->getParsedBody()
            ->willReturn([
                [
                    'id_type' => 'AVATAR'
                ]
            ]);
        $this->shouldThrow(UserErrorException::class)->duringOnBatchRequest($requestMock);
    }

    public function it_should_process_items(ServerRequestInterface $requestMock)
    {
        $requestMock->getParsedBody()
            ->willReturn([
                [
                    'id_type' => 'GUID',
                    'id' => 123,
                    'membership_guid' => 456,
                    'valid_from' => '2024-05-01',
                    'valid_to' => '2024-06-01',
                ]
            ]);

        $this->batchServiceMock->process(Argument::any())
            ->shouldBeCalled();

        $response = $this->onBatchRequest($requestMock);
        $response->getStatusCode()->shouldBe(200);
    }
}
