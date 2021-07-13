<?php
/**
 * FAQ Question
 */
namespace Minds\Core\Faq;

class Question implements \JsonSerializable
{
    /** @var string */
    protected $question = '';

    /** @var Answer */
    protected $answer;

    public function setQuestion(string $question)
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestion()
    {
        return $this->question;
    }

    public function setAnswer(Answer $answer)
    {
        $this->answer = $answer;
        return $this;
    }

    public function getAnswer()
    {
        return $this->answer;
    }

    public function jsonSerialize()
    {
        return [
            'question' => $this->getQuestion(),
            'answer' => $this->getAnswer()
        ];
    }
}
