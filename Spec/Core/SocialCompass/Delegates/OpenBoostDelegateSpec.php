<?php

namespace Spec\Minds\Core\SocialCompass\Delegates;

use Cassandra\Bigint;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\SocialCompass\Delegates\OpenBoostDelegate;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class OpenBoostDelegateSpec extends ObjectBehavior
{
    private $entitiesBuilder;
    private $saveAction;

    public function let(
        EntitiesBuilder $entitiesBuilder = null,
        Save $saveAction = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->saveAction = $saveAction;

        $this->beConstructedWith(
            $this->entitiesBuilder,
            $this->saveAction
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OpenBoostDelegate::class);
    }

    public function it_should_perform_actions_if_the_answers_are_above_the_threshold(
        User $user
    ) {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '70')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->saveAction->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save()
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->onAnswersProvided($answers);
    }

    public function it_should_NOT_perform_actions_if_ALL_answers_are_NOT_above_the_threshold()
    {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '69')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldNotBeCalled();

        $this->onAnswersProvided($answers);
    }

    public function it_should_NOT_perform_actions_if_the_first_answer_is_NOT_above_the_threshold()
    {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '70')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldNotBeCalled();

        $this->onAnswersProvided($answers);
    }

    public function it_should_NOT_perform_actions_if_the_second_answer_is_NOT_above_the_threshold()
    {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '70')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldNotBeCalled();

        $this->onAnswersProvided($answers);
    }

    public function it_should_NOT_perform_actions_if_the_third_answer_is_NOT_above_the_threshold()
    {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '69')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldNotBeCalled();

        $this->onAnswersProvided($answers);
    }

    public function it_should_NOT_perform_actions_if_first_two_answers_are_NOT_above_the_threshold()
    {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '70')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldNotBeCalled();

        $this->onAnswersProvided($answers);
    }

    public function it_should_NOT_perform_actions_if_second_two_answers_are_NOT_above_the_threshold()
    {
        $userGuidBigInt = new Bigint(000000000001);
        $answers = [
            new AnswerModel($userGuidBigInt, 'ChallengingOpinionsQuestion', '70'),
            new AnswerModel($userGuidBigInt, 'DebatedMisinformationQuestion', '69'),
            new AnswerModel($userGuidBigInt, 'MatureContentQuestion', '69')
        ];

        $this->entitiesBuilder->single($userGuidBigInt)
            ->shouldNotBeCalled();

        $this->onAnswersProvided($answers);
    }
}
