<?php

namespace Spec\Minds\Core\Security\AbuseGuard;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Entities\User;
use Minds\Core\Security\AbuseGuard\AccusedEntity;

class RecoverSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Security\AbuseGuard\Recover');
    }

    public function it_should_set_accused(AccusedEntity $accused)
    {
        $this->setAccused($accused)->shouldReturn($this);
    }

    public function it_should_recover(Client $client, AccusedEntity $accused, User $user)
    {
        $this->beConstructedWith($client);

        $user->get('guid')->willReturn(123);
        $accused->getUser()->willReturn($user);
        $this->setAccused($accused);

        $this->recover()->shouldBe(true);
    }
}
