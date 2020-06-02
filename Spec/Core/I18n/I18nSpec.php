<?php

namespace Spec\Minds\Core\I18n;

use Minds\Core\I18n\I18n;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class I18nSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(I18n::class);
    }

    public function it_should_return_languages_list()
    {
        $languages = $this->getLanguages();
        $languages['en']->shouldBe('English (English)');
        $languages['fr']->shouldBe('français (French)');
        $languages['es']->shouldBe('español (Spanish)');
        $languages['th']->shouldBe('ไทย (Thai)');
    }
}
