<?php

namespace Spec\Minds\Core\Media\Video\Transcoder;

use Minds\Core\Media\Video\Transcoder\Repository;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Core\Data\Cassandra\Client;
use Minds\Entities\Video;
use Spec\Minds\Mocks\Cassandra\Rows;
use Cassandra\Bigint;
use Cassandra\Timestamp;
use Cassandra\Varint;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $db;

    public function let(Client $db)
    {
        $this->beConstructedWith($db);
        $this->db = $db;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add(Transcode $transcode)
    {
        $transcode->getGuid()
            ->shouldBeCalled()
            ->willReturn("123");

        $transcode->getProfile()
            ->shouldBeCalled()
            ->willReturn(new TranscodeProfiles\X264_360p());

        $transcode->getStatus()
            ->shouldBeCalled()
            ->willReturn('created');

        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled();

        $this->add($transcode)
            ->shouldReturn(true);
    }

    public function it_should_get_single()
    {
        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'guid' => new Bigint(123),
                    'profile_id' => 'X264_360p',
                    'last_event_timestamp_ms' => new Timestamp(microtime(true)),
                    'progress' => new Varint(0),
                    'status' => null,
                    'length_secs' => new Varint(0),
                    'bytes' => new Varint(0),
                ]
                ], null));

        $transcode = $this->get("urn:transcode:123-X264_360p");
        $transcode->getGuid()
            ->shouldBe("123");
    }

    public function it_should_get_list()
    {
        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'guid' => new Bigint(123),
                    'profile_id' => 'X264_360p',
                    'last_event_timestamp_ms' => new Timestamp(microtime(true)),
                    'progress' => new Varint(0),
                    'status' => null,
                    'length_secs' => new Varint(0),
                    'bytes' => new Varint(0),
                ],
                [
                    'guid' => new Bigint(456),
                    'profile_id' => 'X264_720p',
                    'last_event_timestamp_ms' => new Timestamp(microtime(true)),
                    'progress' => new Varint(0),
                    'status' => null,
                    'length_secs' => new Varint(0),
                    'bytes' => new Varint(0),
                ]
            ], null));

        $rows = $this->getList([]);

        $X264_360Transcode = $rows[0];
        $X264_360Transcode->getGuid()
            ->shouldBe("123");

        $X264_720Transcode = $rows[1];
        $X264_720Transcode->getGuid()
            ->shouldBe("456");
    }

    public function it_should_update(Transcode $transcode, Video $video)
    {
        $transcode->getGuid()
            ->shouldBeCalled()
            ->willReturn("123");

        $transcode->getProfile()
            ->shouldBeCalled()
            ->willReturn(new TranscodeProfiles\X264_360p());

        $transcode->setLastEventTimestampMs(Argument::approximate(round(microtime(true) * 1000), -4))
            ->shouldBeCalled();

        $transcode->getLastEventTimestampMs()
            ->shouldBeCalled()
            ->willReturn(round(microtime(true) * 1000));

        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled();

        $this->update($transcode, [])
            ->shouldReturn(true);
    }

    public function it_should_delete(Transcode $transcode)
    {
        $transcode->getGuid()
            ->shouldBeCalled()
            ->willReturn("123");

        $transcode->getProfile()
            ->shouldBeCalled()
            ->willReturn(new TranscodeProfiles\X264_360p());

        $this->db->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled();

        $this->delete($transcode)
            ->shouldReturn(true);
    }
}
