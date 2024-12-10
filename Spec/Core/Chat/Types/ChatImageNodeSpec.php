<?php

namespace Spec\Minds\Core\Chat\Types;

use DateTime;
use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Chat\Types\ChatImageNode;
use PhpSpec\ObjectBehavior;
use TheCodingMachine\GraphQLite\Types\ID;

class ChatImageNodeSpec extends ObjectBehavior
{
    private ChatImage $chatImage;

    public function let()
    {
        $this->chatImage = new ChatImage(
            guid: 1,
            roomGuid: 2,
            messageGuid: 3,
            width: 100,
            height: 100,
            blurhash: 'blurhash',
            createdTimestamp: new DateTime('2024-01-01'),
            updatedTimestamp: new DateTime('2024-02-01')
        );
        $this->beConstructedWith($this->chatImage);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatImageNode::class);
    }

    public function it_should_return_id()
    {
        $this->getId()->shouldBeLike(new ID('chat-image-1'));
    }

    public function it_should_return_guid()
    {
        $this->getGuid()->shouldBe('1');
    }

    public function it_should_return_url()
    {
        $this->getUrl()->shouldContain('/fs/v3/chat/image/2/3');
    }

    public function it_should_return_width()
    {
        $this->getWidth()->shouldBe(100);
    }

    public function it_should_return_height()
    {
        $this->getHeight()->shouldBe(100);
    }

    public function it_should_return_blurhash()
    {
        $this->getBlurhash()->shouldBe('blurhash');
    }

    public function it_should_return_created_timestamp_formatted_as_iso_8601()
    {
        $this->getCreatedTimestampISO8601()->shouldBeLike((new DateTime('2024-01-01'))->format('c'));
    }

    public function it_should_return_updated_timestamp_formatted_as_iso_8601()
    {
        $this->getUpdatedTimestampISO8601()->shouldBeLike((new DateTime('2024-02-01'))->format('c'));
    }

    public function it_should_return_created_timestamp_formatted_as_unix()
    {
        $this->getCreatedTimestampUnix()->shouldBeLike((new DateTime('2024-01-01'))->format('U'));
    }

    public function it_should_return_updated_timestamp_formatted_as_unix()
    {
        $this->getUpdatedTimestampUnix()->shouldBeLike((new DateTime('2024-02-01'))->format('U'));
    }
}
