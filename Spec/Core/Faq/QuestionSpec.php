<?php

namespace Spec\Minds\Core\Faq;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Faq\Answer;

class QuestionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Faq\Question');
    }

    public function it_should_set_question()
    {
        $this->setQuestion('question')->shouldReturn($this);
        $this->getQuestion()->shouldBe('question');
    }

    public function it_should_set_answer(Answer $answer)
    {
        $this->setAnswer($answer)->shouldReturn($this);
        $this->getAnswer()->shouldBe($answer);
    }
}
