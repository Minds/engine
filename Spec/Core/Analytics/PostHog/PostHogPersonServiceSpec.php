<?php

namespace Spec\Minds\Core\Analytics\PostHog;

use Minds\Core\Analytics\PostHog\PostHogConfig;
use Minds\Core\Analytics\PostHog\PostHogPersonService;
use GuzzleHttp\Client as HttpClient;
use Minds\Core\Guid;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Zend\Diactoros\Response\JsonResponse;

class PostHogPersonServiceSpec extends ObjectBehavior
{
    private Collaborator $postHogConfigMock;
    private Collaborator $httpClientMock;

    public function let(PostHogConfig $postHogConfigMock, HttpClient $httpClientMock)
    {
        $this->beConstructedWith($postHogConfigMock, $httpClientMock);

        $this->postHogConfigMock = $postHogConfigMock;
        $this->httpClientMock = $httpClientMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PostHogPersonService::class);
    }

    public function it_should_get_person_if_exists()
    {
        $guid = Guid::build();

        $this->postHogConfigMock->getProjectId()
            ->willReturn('001');
    
        $this->httpClientMock->get("api/projects/001/persons", [
            'query' => [
                'distinct_id' => $guid,
            ]
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'results' => [
                    [
                        'id' => 'uuid'
                    ]
                ]
            ]));

        $response = $this->getPerson($guid);
        $response->id->shouldBe('uuid');
    }

    public function it_should_not_return_person_if_no_records()
    {
        $guid = Guid::build();

        $this->postHogConfigMock->getProjectId()
            ->willReturn('001');
    
        $this->httpClientMock->get("api/projects/001/persons", [
            'query' => [
                'distinct_id' => $guid,
            ]
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'results' => [ ]
            ]));

        $this->shouldThrow(NotFoundException::class)->duringGetPerson($guid);
    }

    public function it_should_delete_person()
    {
        $guid = Guid::build();

        $this->postHogConfigMock->getProjectId()
            ->willReturn('001');
    
        $this->httpClientMock->get("api/projects/001/persons", [
            'query' => [
                'distinct_id' => $guid,
            ]
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'results' => [
                    [
                        'id' => 'uuid'
                    ]
                ]
            ]));

        $this->httpClientMock->delete("api/projects/001/persons/uuid")
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([], 204));

        $this->deletePerson($guid)->shouldBe(true);
    }

    public function it_should_return_false_if_not_204_response()
    {
        $guid = Guid::build();

        $this->postHogConfigMock->getProjectId()
            ->willReturn('001');
    
        $this->httpClientMock->get("api/projects/001/persons", [
            'query' => [
                'distinct_id' => $guid,
            ]
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'results' => [
                    [
                        'id' => 'uuid'
                    ]
                ]
            ]));

        $this->httpClientMock->delete("api/projects/001/persons/uuid")
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([], 500));

        $this->deletePerson($guid)->shouldBe(false);
    }
}
