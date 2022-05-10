<?php

namespace Spec\Minds\Core\Feeds\UnseenTopFeed;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\UnseenTopFeed\Controller;
use Minds\Core\Feeds\UnseenTopFeed\Manager;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
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

    public function it_should_return_bad_request_with_limit_property_missing()
    {
        $request = (new ServerRequest())
            ->withMethod("GET")
            ->withQueryParams([]);

        $this
            ->shouldThrow(UserErrorException::class)
            ->during("getUnseenTopFeed", [$request]);
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
        ElasticManager $elasticManager
    ) {
        $request = (new ServerRequest())
            ->withMethod("GET")
            ->withAttribute("_user", new User(1))
            ->withQueryParams([
                'limit' => 1
            ]);

        $expectedEntities = new Response([
            (new FeedSyncEntity())->setGuid(1)
        ]);

        $elasticManager
            ->getList(Argument::withEntry('unseen', true))
            ->shouldBeCalledOnce()
            ->willReturn($expectedEntities);

        $this->beConstructedWith($elasticManager);

        $response = $this
            ->getUnseenTopFeed($request)
            ->getPayload();

        $response
            ->shouldContainFieldWithValue("status", "success");

        $response
            ->shouldContainFieldWithValue("entities", Exportable::_($expectedEntities));
    }
}
