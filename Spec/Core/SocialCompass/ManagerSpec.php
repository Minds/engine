<?php

namespace Spec\Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Delegates\ActionDelegateManager;
use Minds\Core\SocialCompass\Manager;
use Minds\Core\SocialCompass\Questions\BannedMisinformationQuestion;
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
        $questions["questions"]->shouldContainValueLike(new BannedMisinformationQuestion());
    }

    public function it_should_retrieve_social_compass_questions_with_active_user(
        RepositoryInterface $repository,
        User $targetUserMock,
        ActionDelegateManager $actionDelegateManager
    ) {
        $targetUserMock
            ->getGuid()
            ->shouldBeCalled()
            ->willReturn(1);

        $repository
            ->getAnswers(Argument::any())
            ->shouldBeCalled()
            ->willReturn([]);

        $this->beConstructedWith($repository, $targetUserMock, $actionDelegateManager);

        $this
            ->retrieveSocialCompassQuestions()["questions"]
            ->shouldContainValueLike(new BannedMisinformationQuestion());
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
        User $targetUserMock,
        ActionDelegateManager $actionDelegateManager
    ) {
        $targetUserMock
            ->getGuid()
            ->willReturn(1);

        $repository
            ->storeAnswers(Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($repository, $targetUserMock, $actionDelegateManager);

        $this
            ->storeSocialCompassAnswers([])
            ->shouldBe(true);
    }

    public function it_should_update_social_compass_answers(
        RepositoryInterface $repository,
        User $targetUserMock,
        ActionDelegateManager $actionDelegateManager
    ) {
        $targetUserMock
            ->getGuid()
            ->willReturn(1);

        $repository
            ->storeAnswers(Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($repository, $targetUserMock, $actionDelegateManager);

        $this
            ->updateSocialCompassAnswers([])
            ->shouldBe(true);
    }
}
