<?php

namespace Spec\Minds\Core\Hashtags\User;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Hashtags\User\PseudoHashtags;
use Minds\Core\Hashtags\HashtagEntity;
use Minds\Core\Data\Cassandra;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PseudoHashtagsSpec extends ObjectBehavior
{
    private $db;
    private $pseuodId;

    public function let(Cassandra\Client $db, PseudonymousIdentifier $pseudoId)
    {
        $this->beConstructedWith($db, $pseudoId);
        $this->db = $db;
        $this->pseuodId = $pseudoId;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PseudoHashtags::class);
    }

    public function it_should_sync_tags()
    {
        $this->pseuodId->getId()
            ->willReturn('pseudo_id_1');

        $this->db->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0] === 'pseudo_id_1';
        }), true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->syncTags([
            (new HashtagEntity())
                ->setHashtag('music'),
            (new HashtagEntity())
                ->setHashtag('art'),
        ])
            ->shouldBe(true);
    }

    public function it_should_remove_tags()
    {
        $this->pseuodId->getId()
            ->willReturn('pseudo_id_2');

        $this->db->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0] === 'pseudo_id_2';
        }), true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->removeTags([
            (new HashtagEntity())
                ->setHashtag('music'),
            (new HashtagEntity())
                ->setHashtag('art'),
        ])
            ->shouldBe(true);
    }
}
