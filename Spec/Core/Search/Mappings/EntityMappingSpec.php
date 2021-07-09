<?php

namespace Spec\Minds\Core\Search\Mappings;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EntityMappingSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Mappings\EntityMapping');
    }

    public function it_should_set_entity(
        \ElggEntity $entity
    ) {
        $this->setEntity($entity)
            ->shouldReturn($this);
    }

    public function it_should_get_type(
        \ElggEntity $entity
    ) {
        $entity->get('type')->willReturn('entity');
        $entity->get('subtype')->willReturn('');

        $this
            ->setEntity($entity)
            ->getType()
            ->shouldReturn('entity');
    }

    public function it_should_get_type_with_subtype(
        \ElggEntity $entity
    ) {
        $entity->get('type')->willReturn('entity');
        $entity->get('subtype')->willReturn('sub');

        $this
            ->setEntity($entity)
            ->getType()
            ->shouldReturn('entity-sub');
    }

    public function it_should_throw_during_get_type_if_no_entity()
    {
        $this
            ->shouldThrow(\Exception::class)
            ->duringGetType();
    }

    public function it_should_get_id(
        \ElggEntity $entity
    ) {
        $entity->get('guid')->willReturn(5000);

        $this
            ->setEntity($entity)
            ->getId()
            ->shouldReturn('5000');
    }

    public function it_should_throw_during_get_id_if_no_entity()
    {
        $this
            ->shouldThrow(\Exception::class)
            ->duringGetId();
    }

    public function it_should_get_mappings()
    {
        $mappings = $this->getMappings();

        // Assert for common props
        $mappings->shouldHaveKey('@timestamp');
        $mappings->shouldHaveKey('guid');
        $mappings->shouldHaveKey('type');
        $mappings->shouldHaveKey('subtype');
    }

    public function it_should_map_an_entity(
        \Minds\Entities\Activity $entity
    ) {
        $now = time();

        $entity->get('interactions')->willReturn(42);
        $entity->get('guid')->willReturn(5000);
        $entity->get('type')->willReturn('entity');
        $entity->get('subtype')->willReturn('');
        $entity->get('time_created')->willReturn($now);
        $entity->get('access_id')->willReturn(2);
        $entity->get('owner_guid')->willReturn(1000);
        $entity->get('container_guid')->willReturn(1000);
        $entity->get('mature')->willReturn(false);
        $entity->getMature()->willReturn(false);
        $entity->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $entity->get('name')->willReturn('PHPSpec Name');
        $entity->get('title')->willReturn('PHPSpec Title');
        $entity->get('blurb')->willReturn('PHPSpec Blurb');
        $entity->get('description')->willReturn('PHPSpec Description');
        $entity->get('paywall')->willReturn(false);
        $entity->isPayWall()->willReturn(false);
        $entity->get('tags')->willReturn(['test', 'hashtag']);
        $entity->get('rating')->willReturn(1);
        $entity->get('moderator_guid')->willReturn('123');
        $entity->get('time_moderated')->willReturn($now);
        $entity->getTags()->willReturn([ 'hashtag', 'spaceiscool' ]);
        $entity->getNsfw()->willReturn([ 1 ]);
        $entity->getWireThreshold()->willReturn(null);
        $entity->get('language')->willReturn('en');

        $this
            ->setEntity($entity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'type' => 'entity',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'message' => 'PHPSpec Message #test #hashtag',
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                '@timestamp' => $now * 1000,
                'public' => true,
                // 'wire_support_tier' => 'urn:support-tier:123456',
                // '@wire_support_tier_expire' => 1000000,
                'tags' => [ 'hashtag', 'spaceiscool', 'test' ],
                'nsfw' => [ 1 ],
                'moderator_guid' => '123',
                '@moderated' => $now * 1000
            ]);
    }

    public function it_should_throw_during_map_if_no_entity()
    {
        $this
            ->shouldThrow(\Exception::class)
            ->duringMap();
    }

    public function it_should_suggest_map(
        \ElggEntity $entity
    ) {
        $this
            ->setEntity($entity)
            ->suggestMap([
                'passedValue' => 'PHPSpec'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec'
            ]);
    }
}
