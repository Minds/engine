<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Core\Boost\Network\ElasticRepository;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Boost\Raw\ElasticRepository as RawElasticRepository;
use Minds\Core\Boost\Raw\RawBoost;
use Minds\Core\Data\ElasticSearch\Client as Elastic;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ElasticRepositorySpec extends ObjectBehavior
{
    private $rawElasticRepository;

    function let(RawElasticRepository $rawElasticRepository)
    {
        $this->beConstructedWith($rawElasticRepository);
        $this->rawElasticRepository = $rawElasticRepository;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ElasticRepository::class);
    }

    function it_should_add(Boost $boost)
    {
        // TODO: Improve test case prophecies

        $this->rawElasticRepository->add(Argument::type(RawBoost::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($boost)
            ->shouldReturn(true);
    }

}
