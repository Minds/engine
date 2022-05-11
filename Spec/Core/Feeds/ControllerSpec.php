<?php

namespace Spec\Minds\Core\Feeds;

use \Minds\Core\Feeds\Controller;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use \Minds\Core\Feeds\Elastic;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;
use Minds\Common\Repository\Response;

class ControllerSpec extends ObjectBehavior
{
    /** @var Elastic\Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Config */
    protected $config;

    public function let(
        Elastic\Manager $manager = null,
        EntitiesBuilder $entitiesBuilder = null,
        Config $config = null
    ) {
        $this->beConstructedWith($manager, $entitiesBuilder, $config);
        $this->manager = $manager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_get_feed_from_manager(ServerRequest $request, Response $response)
    {
        $this->config->get('default_recommendations_user')
            ->shouldBeCalled()
            ->willReturn('1000');

        $this->manager->getList([
            'cache_key' => '1000',
            'subscriptions' => '1000',
            'access_id' => 2,
            'limit' => 12,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '1y',
            'single_owner_threshold' => 36,
            'from_timestamp' => 0,
            'nsfw' => [],
            'unseen' => false
        ])->shouldBeCalled()
          ->willReturn($response);

        $this->getDefaultFeed($request);
    }
}
