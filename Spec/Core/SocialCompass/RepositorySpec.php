<?php

namespace Spec\Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Core\SocialCompass\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class RepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_answers_and_return_an_array_with_the_entries_found(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'user_guid' => new Bigint(1),
                    'question_id' => 'EstablishmentQuestion',
                    'current_value' => 50
                ]
            ], ""));

        $this->beConstructedWith($cassandraClientMock);

        $this
            ->getAnswers(1)
            ->shouldHaveCount(1);
    }

    public function it_should_get_answers_and_return_null_when_no_entries_found(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $this->beConstructedWith($cassandraClientMock);

        $this
            ->getAnswers(1)
            ->shouldBe(null);
    }

    public function it_should_get_answers_and_return_false_when_an_error_occurred(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(false);

        $this->beConstructedWith($cassandraClientMock);

        $this
            ->getAnswers(1)
            ->shouldBe(false);
    }

    public function it_should_get_answer_by_question_id_and_return_an_answer_entity_when_answer_is_found(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'user_guid' => new Bigint(1),
                    'question_id' => 'EstablishmentQuestion',
                    'current_value' => 50
                ]
            ], ""));
        $this->beConstructedWith($cassandraClientMock);

        $this
            ->getAnswerByQuestionId(1, "EstablishmentQuestion")
            ->shouldHaveType(AnswerModel::class);
    }

    public function it_should_get_answer_by_question_id_and_return_null_when_no_answer_is_found(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);
        $this->beConstructedWith($cassandraClientMock);

        $this
            ->getAnswerByQuestionId(1, "EstablishmentQuestion")
            ->shouldBe(null);
    }

    public function it_should_get_answer_by_question_id_and_return_false_when_an_error_occurred(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(false);
        $this->beConstructedWith($cassandraClientMock);

        $this
            ->getAnswerByQuestionId(1, "EstablishmentQuestion")
            ->shouldBe(false);
    }

    public function it_should_store_answers_and_return_true_if_successful(
        Client $cassandraClientMock,
        Rows $rowsMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn($rowsMock);
        $this->beConstructedWith($cassandraClientMock);
        $answers = [
            new AnswerModel(
                new Bigint(1),
                "EstablishmentQuestion",
                50
            )
        ];

        $this
            ->storeAnswers($answers)
            ->shouldBe(true);
    }

    public function it_should_store_answers_and_return_false_if_one_or_more_answers_not_stored(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(false);
        $this->beConstructedWith($cassandraClientMock);
        $answers = [
            "EstablishmentQuestion" => 50
        ];

        $this
            ->storeAnswers(1, $answers)
            ->shouldBe(false);
    }

    public function it_should_update_answers_and_return_true_if_successful(
        Client $cassandraClientMock,
        Rows $rowsMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn($rowsMock);
        $this->beConstructedWith($cassandraClientMock);
        $answers = [
            "EstablishmentQuestion" => 50
        ];

        $this
            ->updateAnswers(1, $answers)
            ->shouldBe(true);
    }

    public function it_should_update_answers_and_return_false_if_one_or_more_answers_not_stored(
        Client $cassandraClientMock
    ) {
        $cassandraClientMock
            ->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(false);
        $this->beConstructedWith($cassandraClientMock);
        $answers = [
            "EstablishmentQuestion" => 50
        ];

        $this
            ->updateAnswers(1, $answers)
            ->shouldBe(false);
    }
}
