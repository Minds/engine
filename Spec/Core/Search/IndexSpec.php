<?php

namespace Spec\Minds\Core\Search;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Update;
use Minds\Core\Hashtags\WelcomeTag\Manager as WelcomeTagManager;
use Minds\Core\Search\Hashtags\Manager;
use Minds\Core\Search\Mappings\Factory;
use Minds\Core\Search\Mappings\MappingInterface;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IndexSpec extends ObjectBehavior
{
    protected $clientMock;
    protected $indexPrefixMock = 'phpspec';
    protected $mappingFactoryMock;
    protected $welcomeTagManager;

    public function let(
        Client $clientMock,
        Factory $mappingFactoryMock,
        Manager $hashtagManagerMock,
        WelcomeTagManager $welcomeTagManager
    ) {
        $this->clientMock = $clientMock;
        $this->mappingFactoryMock = $mappingFactoryMock;
        $this->welcomeTagManager = $welcomeTagManager;
        $this->beConstructedWith($clientMock, $this->indexPrefixMock, $hashtagManagerMock, null, $mappingFactoryMock, $welcomeTagManager);
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

    public function it_should_index_without_hellominds_tag_when_a_user_is_not_eligible_but_one_is_provided(
        Activity $entity,
        MappingInterface $mapper
    ) {
        $ownerGuid = '2345';
        $tags = ['hellominds'];

        $this->mappingFactoryMock->build($entity)
            ->shouldBeCalled()
            ->willReturn($mapper);

        $mapper->map()
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '1000',
                'type' => 'test',
                'tags' => $tags,
                'owner_guid' => $ownerGuid
            ]);

        $this->welcomeTagManager->remove($tags)
            ->shouldBeCalled();

        $this->welcomeTagManager->shouldAppend($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

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
                $query['body']['doc']['tags'] === [] &&
                isset($query['body']['doc']['suggest'])
            ;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->index($entity)
            ->shouldReturn(true);
    }

    public function it_should_index_without_hellominds_tag_when_a_user_is_not_eligible_and_one_is_not_provided(
        Activity $entity,
        MappingInterface $mapper
    ) {
        $ownerGuid = '2345';
        $tags = [];

        $this->mappingFactoryMock->build($entity)
            ->shouldBeCalled()
            ->willReturn($mapper);

        $mapper->map()
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '1000',
                'type' => 'test',
                'tags' => $tags,
                'owner_guid' => $ownerGuid
            ]);

        $this->welcomeTagManager->remove($tags)
            ->shouldBeCalled();

        $this->welcomeTagManager->shouldAppend($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

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
                $query['body']['doc']['tags'] === [] &&
                isset($query['body']['doc']['suggest'])
            ;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->index($entity)
            ->shouldReturn(true);
    }

    public function it_should_index_with_hellominds_tag_when_a_user_is_eligible(
        Activity $entity,
        MappingInterface $mapper
    ) {
        $ownerGuid = '2345';
        $tags = [];

        $this->mappingFactoryMock->build($entity)
            ->shouldBeCalled()
            ->willReturn($mapper);

        $mapper->map()
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '1000',
                'type' => 'test',
                'tags' => $tags,
                'owner_guid' => $ownerGuid
            ]);

        $this->welcomeTagManager->remove($tags)
            ->shouldBeCalled();

        $this->welcomeTagManager->shouldAppend($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->welcomeTagManager->append($tags)
            ->shouldBeCalled()
            ->willReturn(['hellominds']);

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
                $query['body']['doc']['tags'] === ['hellominds'] &&
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
