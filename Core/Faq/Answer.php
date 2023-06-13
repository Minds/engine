<?php
/**
 * FAQ Answer
 */
namespace Minds\Core\Faq;

class Answer implements \JsonSerializable
{
    protected $answer = '';
    protected $question;

    public function setQuestion(Question $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;
        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function jsonSerialize(): string
    {
        return $this->getAnswer();
    }
}
