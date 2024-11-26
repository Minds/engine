<?php

namespace Spec\Minds\Integrations\MemberSpace;

use GuzzleHttp\Client;
use Minds\Integrations\MemberSpace\MemberSpaceService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class MemberSpaceServiceSpec extends ObjectBehavior
{
    private Collaborator $httpClientMock;

    public function let(Client $httpClientMock)
    {
        $this->beConstructedWith($httpClientMock);
        $this->httpClientMock = $httpClientMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MemberSpaceService::class);
    }

    public function it_should_return_memberspace_profile()
    {
        $this->httpClientMock->get(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'id' => 1,
                'email' => 'test@test.com',
                'name' => 'My Name'
            ]));
        $profile = $this->getProfile('abc');
        $profile->id->shouldBe(1);
        $profile->email->shouldBe('test@test.com');
        $profile->name->shouldBe('My Name');
    }
}
