<?php

namespace Spec\Minds\Core\Channels;

use Minds\Core\Channels\AvatarService;
use Minds\Core\Config\Config;
use Minds\Core\Data\Call;
use Minds\Core\Media\Proxy\Download;
use Minds\Core\Media\Imagick;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AvatarServiceSpec extends ObjectBehavior
{
    protected $downloadServiceMock;
    protected $configMock;
    protected $imagickManagerMock;
    protected $entitiesDbMock;

    public function let(Download $downloadServiceMock, Config $configMock, Imagick\Manager $imagickManagerMock, Call $entitiesDbMock)
    {
        $this->beConstructedWith($downloadServiceMock, $configMock, $imagickManagerMock, $entitiesDbMock);
        $this->downloadServiceMock = $downloadServiceMock;
        $this->configMock = $configMock;
        $this->imagickManagerMock = $imagickManagerMock;
        $this->entitiesDbMock = $entitiesDbMock;

        $this->configMock->get('icon_sizes')
            ->willReturn([]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AvatarService::class);
    }

    public function it_should_create_avatar_from_url()
    {
        $fakeUrl = "https://fakeurl.minds.com/avatar-to-fetch-from-here";

        $user = new User();
        $user->guid = '123';

        //

        $this->downloadServiceMock->setSrc($fakeUrl)
            ->willReturn($this->downloadServiceMock);

        $this->downloadServiceMock->downloadBinaryString()
            ->willReturn("");

        //

        $this->entitiesDbMock->insert('123', Argument::any())
            ->willReturn(true);

        //

        $this->withUser($user)->createFromUrl($fakeUrl)
            ->shouldBe(true);
    }

    public function it_should_create_avatar_from_local_path()
    {
        $user = new User();
        $user->guid = '123';

        $this->entitiesDbMock->insert('123', Argument::any())
            ->willReturn(true);

        //

        $this->withUser($user)->createFromFile('/tmp/fake-file-path')
            ->shouldBe(true);
    }
}
