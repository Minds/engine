<?php

namespace Spec\Minds\Core\Search;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Update;
use Minds\Core\Search\Hashtags\Manager;
use Minds\Core\Search\Mappings\Factory;
use Minds\Core\Search\Mappings\MappingInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IndexSpec extends ObjectBehavior
{
    protected $clientMock;
    protected $indexPrefixMock = 'phpspec';
    protected $mappingFactoryMock;

    public function let(
        Client $clientMock,
        Factory $mappingFactoryMock,
        Manager $hashtagManagerMock,
    ) {
        $this->clientMock = $clientMock;
        $this->mappingFactoryMock = $mappingFactoryMock;

        $this->beConstructedWith($clientMock, $this->indexPrefixMock, $hashtagManagerMock, null, $mappingFactoryMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Index');
    }

    public function it_should_index(
        \ElggEntity $entity,
        MappingInterface $mapper
    ) {
        $this->mappingFactoryMock->build($entity)
            ->shouldBeCalled()
            ->willReturn($mapper);

        $mapper->map()
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '1000',
                'type' => 'test'
            ]);

        $mapper->suggestMap()
            ->shouldBeCalled()
            ->willReturn([
                'input' => [ 'test' ]
            ]);

        $mapper->getType()
            ->shouldBeCalled()
            ->willReturn('test');

        $mapper->getId()
            ->shouldBeCalled()
            ->willReturn('1000');

        $this->clientMock->request(Argument::that(function ($prepared) {
            if (!($prepared instanceof Update)) {
                return false;
            }

            $query = $prepared->build();

            return
                $query['index'] == $this->indexPrefixMock . '-test' &&
                $query['id'] == '1000' &&
                isset($query['body']) &&
                $query['body']['doc']['guid'] == '1000' &&
                $query['body']['doc']['type'] == 'test' &&
                isset($query['body']['doc']['suggest'])
            ;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->index($entity)
            ->shouldReturn(true);
    }
}
