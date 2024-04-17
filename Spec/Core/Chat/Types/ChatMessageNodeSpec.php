<?php

namespace Spec\Minds\Core\Chat\Types;

use Minds\Core\Chat\Entities\ChatMessage;
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

}
