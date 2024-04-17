<?php

namespace Spec\Minds\Core\Chat\Entities;

use Minds\Core\Chat\Entities\ChatMessage;
use PhpSpec\ObjectBehavior;

class ChatMessageSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(1, 2, 3, '');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatMessage::class);
    }

    public function it_should_export_sanitized_plaintext()
    {
        $plainText = 'just <b>for testing</b>';
        $this->beConstructedWith(1, 2, 3, $plainText);

        $export = $this->export();
        $export['plainText']->shouldBe('just &lt;b&gt;for testing&lt;/b&gt;');
    }
}
