<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Repository;
use Minds\Core\Data\Cassandra\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks;
use Minds\Core\Data\Cassandra\Prepared;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    protected $boostCampaignJsonDataMock = [
        'urn' => 'urn:campaign:1234',
        'owner_guid' => '1001',
        'name' => 'Test Campaign 1',
        'type' => 'newsfeed',
        'entity_urns' => [
            'urn:activity:1234'
        ],
        'hashtags' => [],
        'nsfw' => null,
        'start' => 0,
        'end' => 0,
        'budget' => 1,
        'budget_type' => 'tokens',
        'checksum' => '',
        'impressions' => 1000,
        'impressions_met' => null,
        'rating' => null,
        'quality' => null,
        'created_timestamp' => null,
        'reviewed_timestamp' => null,
        'rejected_timestamp' => null,
        'revoked_timestamp' => null,
        'completed_timestamp' => null
    ];

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_a_list_of_campaigns_by_guid()
    {
        $rows = new Mocks\Cassandra\Rows([
            [
                'guid' => 1234,
                'delivery_status' => '',
                'json_data' => json_encode($this->boostCampaignJsonDataMock),
                'owner_guid' => 1234,
                'type' => 'newsfeed'
            ],
        ], '');

        $this->client->request(Argument::type(Prepared\Custom::class))
            ->shouldBeCalled()
            ->willReturn($rows);

        $return = $this->getCampaignByGuid(['guid' => 1234]);

        $return[0]->shouldBeAnInstanceOf(Campaign::class);
    }

    public function it_should_not_return_a_list_if_no_args_passed()
    {
        $this->shouldThrow('Exception')->duringGetCampaignByGuid();
    }

    public function it_should_store_a_boost_campaign()
    {
        $campaign = (new Campaign())
            ->setType('newsfeed')
            ->setUrn('urn:campaign:1234')
            ->setOwnerGuid(1234)
            ->setCreatedTimestamp(1569974400)
            ->setBudget(1)
            ->setBudgetType('tokens');

        $this->client->request(Argument::type(Prepared\Custom::class), true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->putCampaign($campaign)->shouldReturn(true);
    }

    public function it_should_update_a_boost_campaign()
    {
        $campaign = (new Campaign())
            ->setType('newsfeed')
            ->setUrn('urn:campaign:1234')
            ->setOwnerGuid(1234)
            ->setCreatedTimestamp(1569974400)
            ->setBudget(1)
            ->setBudgetType('tokens');

        $this->client->request(Argument::type(Prepared\Custom::class), true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->putCampaign($campaign)->shouldReturn(true);
    }
}
