<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Boost\Campaigns\ElasticRepositoryQueryBuilder;
use PhpSpec\ObjectBehavior;

class ElasticRepositoryQueryBuilderSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ElasticRepositoryQueryBuilder::class);
    }

    public function it_should_reset()
    {
        $this->reset();
    }

    public function it_should_make_a_query()
    {
        $query = [
            'query' => [
                'bool' => [
                    'must' => [],
                    'must_not' => [],
                ],
            ],
            'sort' => [
                '@timestamp' => 'asc',
            ],
        ];
        $this->query()->shouldReturn($query);
    }
}
