<?php

namespace Spec\Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Helpdesk\Question\Manager;
use Minds\Core\Helpdesk\Question\Question;
use Minds\Core\SEO\Sitemaps\Resolvers\HelpdeskResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HelpdeskResolverSpec extends ObjectBehavior
{
    protected $helpdeskQuestionManager;

    public function let(Manager $helpdeskQuestionManager)
    {
        $this->beConstructedWith($helpdeskQuestionManager);
        $this->helpdeskQuestionManager = $helpdeskQuestionManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(HelpdeskResolver::class);
    }

    public function it_should_return_iterable_of_helpdesk_pages()
    {
        $this->helpdeskQuestionManager->getAll([ 'limit' => 5000 ])
            ->shouldBeCalled()
            ->willReturn([
                (new Question)->setUuid(1),
                (new Question)->setUuid(2)
            ]);
        $this->getUrls()->shouldHaveCount(2);
    }
}
