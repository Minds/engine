<?php

namespace Spec\Minds\Core\Email\V2\Delegates;

use Minds\Common\Repository\Response;
use Minds\Core\Email\V2\Campaigns\Recurring\WeMissYou\WeMissYou;
use Minds\Core\Email\V2\Delegates\WeMissYouSender;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class WeMissYouSenderSpec extends ObjectBehavior
{
    /** @var SuggestionsManager $manager */
    private $suggestionsManager;

    public function let(SuggestionsManager $suggestionsManager, WeMissYou $campaign)
    {
        $this->suggestionsManager = $suggestionsManager;
        $this->beConstructedWith($suggestionsManager, $campaign);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(WeMissYouSender::class);
    }

    public function it_should_send(User $user)
    {
        $this->suggestionsManager->setUser($user)
            ->shouldBeCalled();
        $this->suggestionsManager->getList()
            ->shouldBeCalled()
            ->willReturn(new Response());

        $this->send($user);
    }
}
