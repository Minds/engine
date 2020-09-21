<?php

namespace Spec\Minds\Common;

use Minds\Entities\Activity;
use Minds\Entities\MutatableEntityInterface;
use Minds\Common\EntityMutation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EntityMutationSpec extends ObjectBehavior
{
    /** @var MutatableEntityInterface $activity */
    private $activity;

    public function let()
    {
        $activity = new Activity();
        $this->beConstructedWith($activity);
        $this->activity = $activity;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EntityMutation::class);
    }

    public function it_should_return_a_diff_for_field_change()
    {
        $this->activity->setTitle('testing');
        $this->activity->setMessage('my message will remain the same');

        // Mutate
        $this->setTitle('testing has changed');
        $this->setMessage('my message will remain the same');

        $diff = $this->getDiff();

        $diff->shouldHaveCount(1);
        $diff['title']['mutated']->shouldBe('testing has changed');
    }

    public function it_should_confirm_if_message_field_is_mutated()
    {
        $this->activity->setTitle('testing');
        $this->activity->setMessage('my message will change');

        // Mutate
        $this->setTitle('testing');
        $this->setMessage('my message has changed...');

        $this->hasMutated('title')
            ->shouldBe(false);

        $this->hasMutated('message')
            ->shouldBe(true);
    }


    public function it_should_confirm_if_title_field_is_mutated()
    {
        $this->activity->setTitle('testing');
        $this->activity->setMessage('my message will not change');

        // Mutate
        $this->setTitle('changed title');
        $this->setMessage('my message will not change');

        $this->hasMutated('title')
            ->shouldBe(true);

        $this->hasMutated('message')
            ->shouldBe(false);
    }

    public function it_should_confirm_if_multiple_fields_are_mutated()
    {
        $this->activity->setTitle('testing title will change');
        $this->activity->setMessage('my message will not change');

        // Mutate
        $this->setTitle('changed title');
        $this->setMessage('my message changed');

        $this->hasMutated('title')
            ->shouldBe(true);

        $this->hasMutated('message')
            ->shouldBe(true);
    }

    public function it_should_return_mutated_values()
    {
        $this->activity->setTitle('testing will change');
        $this->activity->setMessage('my message will change');
        $this->activity->setNsfw([1,2,3]);

        // Mutate
        $this->setTitle('testing has changed');
        $this->setMessage('my message has changed...');

        $values = $this->getMutatedValues();
        $values->shouldHaveCount(2);

        $values['title']->shouldBe('testing has changed');
        $values['message']->shouldBe('my message has changed...');
    }

    public function it_should_return_mutated_array_values()
    {
        $this->activity->setNsfw([1,2,3]);

        // Mutate
        $this->setNsfw([1]);

        $values = $this->getMutatedValues();
        $values->shouldHaveCount(1);
        $values['nsfw']->shouldBe([1]);
    }
}
