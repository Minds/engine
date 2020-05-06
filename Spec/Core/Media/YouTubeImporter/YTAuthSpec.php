<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Core\Entities\Actions\Save;
use Minds\Core\Data\Call;
use Minds\Core\Media\YouTubeImporter\YTAuth;
use Minds\Core\Media\YouTubeImporter\YTClient;
use Minds\Entities\User;
use Google_Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class YTAuthSpec extends ObjectBehavior
{
    /** @var YTClient */
    protected $ytClient;

    /** @var Save */
    protected $save;

    /** @var Call */
    protected $db;

    public function let(
        YTClient $ytClient,
        Save $save,
        Call $db
    ) {
        $this->ytClient = $ytClient;
        $this->save = $save;
        $this->db = $db;
       
        $this->beConstructedWith(
            $ytClient,
            $save,
            $db
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(YTAuth::class);
    }

    public function it_should_return_auth_url(Google_Client $client)
    {
        $this->ytClient->getClient(false)
            ->willReturn($client);

        $client->createAuthUrl()
            ->willReturn('url');

        $this->connect()->shouldReturn('url');
    }

    public function it_should_disconnect_from_yt()
    {
        $user = new User();
        $user->setYouTubeChannels([ [ 'id' => 'ytId' ]]);

        $this->save->setEntity(Argument::that(function ($user) {
            return $user->getYouTubeChannels() === [];
        }))
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled();

        $this->disconnect($user, 'ytId');
    }

    // public function it_should_fetch_a_token()
    // {
    // }
}
