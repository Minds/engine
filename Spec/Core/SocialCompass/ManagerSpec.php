<?php

namespace Spec\Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Manager;
use Minds\Core\SocialCompass\Questions\EstablishmentQuestion;
use Minds\Core\SocialCompass\RepositoryInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_retrieve_social_compass_questions(
        RepositoryInterface $repository
    ) {
        $request = (new ServerRequest())
            ->withMethod("GET");

        $repository
            ->getAnswerByQuestionId(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $this->beConstructedWith($request, $repository);

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
            }
        ];
    }

    public function it_should_store_social_compass_answers(
        RepositoryInterface $repository
    ) {
        $_SERVER['HTTP_X_XSRF_TOKEN'] = "xsrftoken";
        $request = (new ServerRequest(serverParams: $_SERVER))
            ->withMethod("POST")
            ->withCookieParams([
                'XSRF-TOKEN' => "xsrftoken"
            ]);

        $repository
            ->storeAnswers(Argument::any(), Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($request, $repository);

        $this
            ->storeSocialCompassAnswers([])
            ->shouldBe(true);
    }

    public function it_should_update_social_compass_answers(
        RepositoryInterface $repository
    ) {
        $_SERVER['HTTP_X_XSRF_TOKEN'] = "xsrftoken";
        $request = (new ServerRequest(serverParams: $_SERVER))
            ->withMethod("PUT")
            ->withCookieParams([
                'XSRF-TOKEN' => "xsrftoken"
            ]);

        $repository
            ->storeAnswers(Argument::any(), Argument::type("array"))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->beConstructedWith($request, $repository);

        $this
            ->updateSocialCompassAnswers([])
            ->shouldBe(true);
    }
}
