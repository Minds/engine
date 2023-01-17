<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\InAppVerifyUniquenessNotice;
use PhpSpec\ObjectBehavior;

class InAppVerifyUniquenessNoticeSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InAppVerifyUniquenessNotice::class);
    }
}
