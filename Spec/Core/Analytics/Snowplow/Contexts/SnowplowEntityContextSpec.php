<?php

namespace Spec\Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Core\Analytics\Snowplow\Contexts\SnowplowEntityContext;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SnowplowEntityContextSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SnowplowEntityContext::class);
    }

    public function it_should_return_data()
    {
        $this->setEntityGuid('123');
        $this->setEntityType('object');
        $this->setEntitySubtype('video');
        $this->setEntityOwnerGuid('456');
        $this->setEntityAccessId(2);
        $this->setEntityContainerGuid('456');

        $this->getData()
            ->shouldBe([
                'entity_guid' => '123',
                'entity_type' => 'object',
                'entity_subtype' => 'video',
                'entity_owner_guid' => '456',
                'entity_access_id' => '2',
                'entity_container_guid' => '456',
            ]);
    }
}
