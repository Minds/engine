<?php

namespace Spec\Minds\Core\Notifications\Push\Settings;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Notifications\Push\Settings\PushSetting;
use Minds\Core\Notifications\Push\Settings\Repository;
use Minds\Core\Notifications\Push\Settings\SettingsListOpts;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $cql;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_list()
    {
        $opts = new SettingsListOpts();
        $opts->setUserGuid('123');

        $this->cql->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn([
                [
                    'user_guid' => '123',
                    'notification_group' => 'all',
                    'enabled' => false,
                ]
            ]);

        $response = $this->getList($opts);
        $response->shouldHaveCount(1);
    }

    public function it_should_add_setting(PushSetting $pushSetting)
    {
        $this->cql->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn(true);
    
        $this->add($pushSetting)
            ->shouldReturn(true);
    }
}
