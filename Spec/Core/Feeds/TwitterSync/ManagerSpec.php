<?php

namespace Spec\Minds\Core\Feeds\TwitterSync;

use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\TwitterSync\Manager;
use Minds\Core\Feeds\TwitterSync\Client;
use Minds\Core\Feeds\TwitterSync\Repository;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function let(
        Client $client,
        Repository $repository,
        Config $config,
        EntitiesBuilder $entitiesBuilder,
        Save $save
    ) {
        $this->beConstructedWith($client, $repository, $config, $entitiesBuilder, $save);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
