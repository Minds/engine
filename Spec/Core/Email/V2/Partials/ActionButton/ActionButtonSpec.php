<?php

namespace Spec\Minds\Core\Email\V2\Partials\ActionButton;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\EmailStyles;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\I18n\Translator;
use Minds\Core\Markdown\Markdown;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActionButtonSpec extends ObjectBehavior
{
    public function let(
        Markdown $markdown,
        Config $config,
        EmailStyles $emailStyles,
        Translator $translator
    ) {
        $this->beConstructedWith($markdown, $config, $emailStyles, $translator);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActionButton::class);
    }

    public function it_should_resolve_relative_path()
    {
        $this->setPath('apples');
        $template = $this->build();
        $template->shouldContain('href="https://www.minds.com/apples"');
    }

    public function it_should_resolve_full_path()
    {
        $this->setPath('https://www.minds.com/iamthewalrus');
        $template = $this->build();
        $template->shouldContain('href="https://www.minds.com/iamthewalrus"');
    }
}
