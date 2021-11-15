<?php

namespace Spec\Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Manager;
use Minds\Core\SocialCompass\Questions\EstablishmentQuestion;
use Minds\Core\SocialCompass\RepositoryInterface;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_retrieve_social_compass_questions_with_no_active_user()
    {
        $questions = $this->retrieveSocialCompassQuestions();
        $questions["questions"]->shouldContainValueLike(new EstablishmentQuestion());
    }

    public function it_should_retrieve_social_compass_questions_with_active_user(
        RepositoryInterface $repository,
        User $targetUserMock
    ) {
        $targetUserMock
            ->getGuid()
            ->shouldBeCalled()
            ->willReturn(1);

        $repository
            ->getAnswers(Argument::any())
            ->shouldBeCalled()
            ->willReturn([]);

        $this->beConstructedWith($repository, $targetUserMock);

        $this
            ->retrieveSocialCompassQuestions()["questions"]
            ->shouldContainValueLike(new EstablishmentQuestion());
    }

    public function getMatchers(): array
    {
        return  [
            'containValueLike' => function ($subject, $value) {
                foreach ($subject as $item) {
                    if ($item == $value) {
                        return true;
                    }
                }
                return false;
            }
        ];
    }

    public function it_should_store_social_compass_answers(
        RepositoryInterface $repository,
        User $targetUserMock
    ) {
        $targetUserMock
            ->getGuid()
            ->willReturn(1);

        $repository
            ->storeAnswers(Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($repository, $targetUserMock);

        $this
            ->storeSocialCompassAnswers([])
            ->shouldBe(true);
    }

    public function it_should_update_social_compass_answers(
        RepositoryInterface $repository,
        User $targetUserMock
    ) {
        $targetUserMock
            ->getGuid()
            ->willReturn(1);

        $repository
            ->storeAnswers(Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($repository, $targetUserMock);

        $this
            ->updateSocialCompassAnswers([])
            ->shouldBe(true);
    }
}
