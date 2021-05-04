<?php

namespace Spec\Minds\Core\Notifications\Delegates;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Data\Call;

class PushSettingsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Notifications\Delegates\PushSettingsDelegate');
    }

    public function it_should_return_toggles(Call $db)
    {
        $this->beConstructedWith($db);
        $db->getRow('settings:push:toggles:')->willReturn([]);
        $this->getToggles()->shouldBeArray();
    }

    public function it_should_merge_default_and_saved_toggles(Call $db)
    {
        $this->beConstructedWith($db);
        $db->getRow('settings:push:toggles:')->willReturn(['comment'=>false]);

        $this->getToggles()->shouldHaveKeyWithValue('comment', false);
    }

    public function it_should_set_a_toggle()
    {
        $this->setToggle('tag', false)->shouldReturn($this);
        $this->getToggles()->shouldHaveKeyWithValue('tag', false);
    }

    public function it_should_set_toggles()
    {
        $this->setToggles(['tag1' => true, 'tag2' => true])->shouldReturn($this);
        $this->getToggles()->shouldHaveKeyWithValue('tag1', true);
        $this->getToggles()->shouldHaveKeyWithValue('tag2', true);
    }

    public function it_should_save_toggles(Call $db)
    {
        $this->beConstructedWith($db);
        $db->insert('settings:push:toggles:', Argument::type('array'))->willReturn('settings:push:toggles:');

        $this->save()->shouldReturn($this);
    }
}
