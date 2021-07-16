<?php

namespace Spec\Minds\Core\Boost;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use Minds\Entities\User;
use Minds\Entities\Boost\Network;
use Minds\Core\Data\Call;
use Minds\Core\Data\Interfaces\ClientInterface;
use Minds\Core\EntitiesBuilder;

class NewsfeedSpec extends ObjectBehavior
{
    public function let(EntitiesBuilder $entitiesBuilder, Call $db, User $user)
    {
        //$db->getRow(Argument::type(''))->will

        $this->beConstructedWith($entitiesBuilder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Boost\Newsfeed');
    }
}
