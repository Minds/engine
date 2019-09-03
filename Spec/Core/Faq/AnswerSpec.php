<?php

namespace Spec\Minds\Core\Faq;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Faq\Question;

class AnswerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Faq\Answer');
    }

    public function it_should_set_answer()
    {
        $this->setAnswer('test')->shouldReturn($this);
        $this->getAnswer()->shouldBe('test');
    }

    public function it_should_set_question(Question $question)
    {
        $this->setQuestion($question)->shouldReturn($this);
        $this->getQuestion()->shouldBe($question);
    }
}
