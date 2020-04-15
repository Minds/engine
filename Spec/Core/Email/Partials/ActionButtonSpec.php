<?php

namespace Spec\Minds\Core\Email\Partials;

use Minds\Core\V2\Email\Partials\ActionButton\ActionButton;
use PhpSpec\ObjectBehavior;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\User;
use DomDocument;

class SuggestedChannelsSpec extends ObjectBehavior
{
    private $path = 'testing123';
    private $label = 'Test Label';

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActionButton::class);
    }

    public function it_should_build()
    {
        $this->setLabel($this->$label);
        $dom = new DomDocument();
        $dom->loadHTML($this->build()->getWrappedObject());
        $anchors = $dom->getElementsByTagName('a');
        expect(trim($anchors[1]->nodeValue))->toEqual($this->label);
    }
}
