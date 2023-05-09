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
}
