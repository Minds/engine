<?php

namespace Spec\Minds\Core\Torrent\TorrentBuilders;

use Minds\Core\Media\Services\AWS;
use PhpSpec\ObjectBehavior;

class S3Spec extends ObjectBehavior
{
    public function let(
        AWS $aws
    ): void {
        $this->beConstructedWith($aws);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Torrent\TorrentBuilders\S3');
    }

    public function it_should_build(
        AWS $aws
    ) {
        $this->beConstructedWith($aws);

        $aws->setKey('5000')
            ->shouldBeCalled()
            ->willReturn($aws);

        $aws->getTorrent('128.mp4')
            ->shouldBeCalled()
            ->willReturn('_TORRENT_FILE_');

        $this
            ->setKey('5000')
            ->setFile('128.mp4')
            ->build()
            ->shouldReturn('_TORRENT_FILE_');
    }
}
