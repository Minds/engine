<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\ElasticRepository;
use Minds\Core\Boost\Campaigns\ElasticRepositoryQueryBuilder;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Data\ElasticSearch\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\ElasticSearch\Prepared;

class ElasticRepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    /** @var ElasticRepositoryQueryBuilder */
    protected $queryBuilder;

    public function let(Client $client, ElasticRepositoryQueryBuilder $queryBuilder)
    {
        $this->beConstructedWith($client, $queryBuilder);
        $this->client = $client;
        $this->queryBuilder = $queryBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ElasticRepository::class);
    }

    public function it_should_return_a_list_of_campaigns_matching_criteria()
    {
        $esResult = [
            'hits' => [
                'hits' => [
                    [
                        '_id' => 1234,
                        '_source' => [
                            'type' => 'newsfeed',
                            'owner_guid' => 1234,
                            'name' => 'test',
                            'entity_urns' => null,
                            'hashtags' => null,
                            'nsfw' => null,
                            'start' => 123456,
                            'end' => 234567,
                            'budget' => 1,
                            'budget_type' => 'tokens',
                            'checksum' => '0xdeadbeef',
                            'impressions' => null,
                            'impressions_met' => null,
                            'rating' => 0,
                            'quality' => 0,
                            '@created' => 12345
                        ]
                    ]
                ]
            ]
        ];

        $this->queryBuilder->setOpts(Argument::type('array'))->shouldBeCalled();
        $this->queryBuilder->query()->shouldBeCalled();
        $this->client->request(Argument::type(Prepared\Search::class))->shouldBeCalled()->willReturn($esResult);

        $return = $this->getCampaigns(['guid' => 1234]);
        $return[0]->shouldBeAnInstanceOf(Campaign::class);
    }

    public function it_should_return_a_list_of_boosts_and_campaigns_matching_criteria()
    {
        $esResult = [
            'hits' => [
                'hits' => [
                    [
                        '_index' => 'minds-boost-campaigns',
                        '_id' => 1234,
                        '_source' => [
                            'type' => 'newsfeed',
                            'owner_guid' => 1234,
                            '@timestamp' => 12345
                        ]
                    ],
                    [
                        '_index' => 'minds-boost',
                        '_id' => 1234,
                        '_source' => [
                            'type' => 'newsfeed',
                            'owner_guid' => 1234,
                            '@timestamp' => 12345
                        ]
                    ]
                ]
            ]
        ];

        $this->queryBuilder->setOpts(Argument::type('array'))->shouldBeCalled();
        $this->queryBuilder->query()->shouldBeCalled();
        $this->client->request(Argument::type(Prepared\Search::class))->shouldBeCalled()->willReturn($esResult);

        $return = $this->getCampaignsAndBoosts(['quality' => 5]);
        $return[0]->shouldBeAnInstanceOf(Campaign::class);
        $return[1]->shouldBeAnInstanceOf(Boost::class);
    }

    public function it_should_put_a_campaign_to_es(Campaign $campaign)
    {
        $campaign->getType()->willReturn('newsfeed');
        $campaign->getOwnerGuid()->willReturn('1234');
        $campaign->getName()->willReturn('test');
        $campaign->getEntityUrns()->willReturn(null);
        $campaign->getHashtags()->willReturn(null);
        $campaign->getNsfw()->willReturn(null);
        $campaign->getStart()->willReturn('12345');
        $campaign->getEnd()->willReturn('23456');
        $campaign->getBudget()->willReturn('1');
        $campaign->getBudgetType()->willReturn('tokens');
        $campaign->getChecksum()->willReturn('0xdeadbeef');
        $campaign->getImpressions()->willReturn(null);
        $campaign->getCreatedTimestamp()->willReturn('123456');
        $campaign->getImpressionsMet()->willReturn('2');
        $campaign->getRating()->willReturn('5');
        $campaign->getQuality()->willReturn('5');
        $campaign->getReviewedTimestamp()->willReturn(null);
        $campaign->getRejectedTimestamp()->willReturn(null);
        $campaign->getRevokedTimestamp()->willReturn(null);
        $campaign->getCompletedTimestamp()->willReturn(null);
        $campaign->getGuid()->willReturn('12345');

        $this->client->request(Argument::type(Prepared\Update::class))->shouldBeCalled()->willReturn(true);
        $this->putCampaign($campaign);
    }
}
