<?php

namespace Spec\Minds\Core\Chat\Types;

use DateTime;
use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Types\ChatImageNode;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ChatMessageNodeSpec extends ObjectBehavior
{
    private Collaborator $chatMessageMock;
    private Collaborator $userEdgeMock;

    public function let(ChatMessage $chatMessageMock, UserEdge $userEdgeMock)
    {
        $this->beConstructedWith($chatMessageMock, $userEdgeMock);
        $this->chatMessageMock = $chatMessageMock;
        $this->userEdgeMock = $userEdgeMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatMessageNode::class);
    }

    public function it_should_return_sanitized_plaintext()
    {
        $plainText = 'just <b>for testing</b>';
        $chatMessage = new ChatMessage(1, 2, 3, $plainText);
        $this->beConstructedWith($chatMessage, $this->userEdgeMock);

        $this->getPlainText()->shouldBe('just &lt;b&gt;for testing&lt;/b&gt;');
    }

    public function it_should_return_image_node()
    {
        $roomGuid = 1;
        $userGuid = 2;
        $messageGuid = 3;
        $imageGuid = 4;
        $plainText = 'test';
        $blurhash = 'blurhash';

        $chatImage = new ChatImage(
            guid: $imageGuid,
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            width: 100,
            height: 100,
            blurhash: $blurhash,
            createdTimestamp: new DateTime('2024-01-01'),
            updatedTimestamp: new DateTime('2024-02-01')
        );

        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $userGuid,
            plainText: $plainText,
            image: $chatImage
        );

        $this->beConstructedWith($chatMessage, $this->userEdgeMock);
        $this->getImage()->shouldBeAnInstanceOf(ChatImageNode::class);
    }
}
