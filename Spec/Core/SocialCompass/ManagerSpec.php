<?php

namespace Spec\Minds\Core\SocialCompass;

use Minds\Core\Sessions\ActiveSession;
use Minds\Core\SocialCompass\Manager;
use Minds\Core\SocialCompass\Questions\EstablishmentQuestion;
use Minds\Core\SocialCompass\RepositoryInterface;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_retrieve_social_compass_questions_with_no_active_user()
    {
        $request = (new ServerRequest())
            ->withMethod("GET");

        $this->beConstructedWith($request);

        $questions = $this->retrieveSocialCompassQuestions();
        $questions["questions"]->shouldContainValueLike(new EstablishmentQuestion());
    }

    public function it_should_retrieve_social_compass_questions_with_active_user(
        RepositoryInterface $repository,
        ActiveSession $session
    ) {
        $session
            ->getUser()
            ->willReturn(new User(1));

        $request = (new ServerRequest())
            ->withMethod("GET");

        $repository
            ->getAnswerByQuestionId(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $this->beConstructedWith($request, $repository, $session);

        $this
            ->retrieveSocialCompassQuestions()
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
        ActiveSession $session
    ) {
        $session
            ->getUser()
            ->willReturn(new User(1));

        $_SERVER['HTTP_X_XSRF_TOKEN'] = "xsrftoken";
        $request = (new ServerRequest(serverParams: $_SERVER))
            ->withMethod("POST")
            ->withCookieParams([
                'XSRF-TOKEN' => "xsrftoken"
            ]);

        $repository
            ->storeAnswers(Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($request, $repository, $session);

        $this
            ->storeSocialCompassAnswers([])
            ->shouldBe(true);
    }

    public function it_should_update_social_compass_answers(
        RepositoryInterface $repository,
        ActiveSession $session
    ) {
        $session
            ->getUser()
            ->willReturn(new User(1));

        $_SERVER['HTTP_X_XSRF_TOKEN'] = "xsrftoken";
        $request = (new ServerRequest(serverParams: $_SERVER))
            ->withMethod("PUT")
            ->withCookieParams([
                'XSRF-TOKEN' => "xsrftoken"
            ]);

        $repository
            ->storeAnswers(Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($request, $repository, $session);

        $this
            ->updateSocialCompassAnswers([])
            ->shouldBe(true);
    }
}
