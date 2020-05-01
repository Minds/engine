<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\YouTubeImporter\Repository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(Client $client, EntitiesBuilder $entitiesBuilder)
    {
        $this->client = $client;
        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith($client, $entitiesBuilder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }


    public function it_should_throw_an_exception_if_status_is_invalid()
    {
        $this->shouldThrow(new \Exception('Invalid status param'))
            ->during('getList', [['status' => 'test']]);
    }


    public function it_should_get_videos()
    {
        $this->client->request(Argument::any())
            ->shouldBeCalled();

        $this->getList([
            'status' => 'queued',
            'time_created' => [
                'lt' => 1000,
                'gt' => 100,
            ],
            'youtube_id' => 'test123',
        ])
            ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get_a_count_of_videos_by_status(User $user)
    {
        $user->getGUID()
            ->shouldBeCalled()
            ->willReturn('1');

        $this->client->request(Argument::any())
            ->shouldBeCalledTimes(2)
            ->willReturn(['count' => 3]);

        $this->getCount($user)
            ->shouldReturn([
                'queued' => 3,
                'transcoding' => 3,
            ]);
    }

    public function it_should_get_owners_eligibility()
    {
        $this->client->request(Argument::that(function ($query) {
            return $query->build()['body']['query']['bool']['filter'][3]['term']['owner_guid'] === 1;
        }))
            ->shouldBeCalled()
            ->willReturn(['count' => 3]);

        $this->client->request(Argument::that(function ($query) {
            return $query->build()['body']['query']['bool']['filter'][3]['term']['owner_guid'] === 2;
        }))
            ->shouldBeCalled()
            ->willReturn(['count' => 10]);

        $guids = [1, 2];

        $this->getOwnersEligibility($guids)
            ->shouldReturn([
                1 => 3,
                2 => 10,
            ]);
    }
}
