<?php

namespace Spec\Minds\Core\Feeds;

use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Controller;
use Minds\Core\Feeds\Elastic;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

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
}
