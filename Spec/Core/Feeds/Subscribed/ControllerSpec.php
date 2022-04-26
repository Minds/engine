<?php

namespace Spec\Minds\Core\Feeds\Subscribed;

use Minds\Core\Feeds\Subscribed\Controller;
use Minds\Core\Feeds\Subscribed\Manager;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_return_bad_request_with_from_timestamp_property_missing()
    {
        $request = (new ServerRequest())
            ->withMethod("GET")
            ->withQueryParams([]);

        $this
            ->shouldThrow(UserErrorException::class)
            ->during("getLatestCount", [$request]);
    }


    public function getMatchers(): array
    {
        return  [
            'containFieldWithValue' => function ($subject, $field, $value) {
                if (!isset($subject[$field])) {
                    return false;
                }

                return $subject[$field] == $value;
            }
        ];
    }

    public function it_should_return_successful_response(
        Manager $manager
    ) {
        $request = (new ServerRequest())
            ->withMethod("GET")
            ->withAttribute("_user", new User(1))
            ->withQueryParams([
                'from_timestamp' => 1650033841888
            ]);

        $manager
            ->getLatestCount(Argument::type(User::class), Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->beConstructedWith($manager);

        $response = $this
            ->getLatestCount($request)
            ->getPayload();

        $response->shouldContainFieldWithValue("status", "success");
        $response->shouldContainFieldWithValue("count", 1);
    }
}
