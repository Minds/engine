<?php

namespace Spec\Minds\Helpers;

use PhpSpec\ObjectBehavior;
use Minds\Helpers\Url;

class UrlSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Url::class);
    }

    // stripQueryParameter

    public function it_should_strip_no_query_params_from_a_url_without_query_params()
    {
        $fullUrl = 'https://example.minds.com';
        $this->stripQueryParameter($fullUrl, 'key')->shouldBe($fullUrl);
    }

    public function it_should_NOT_strip_any_query_params_from_a_url_when_a_key_is_not_present()
    {
        $fullUrl = 'https://example.minds.com?otherKey=otherValue';
        $this->stripQueryParameter($fullUrl, 'key')->shouldBe($fullUrl);
    }
    
    public function it_should_strip_a_given_query_param_from_a_url_with_many_query_params()
    {
        $fullUrl = 'https://example.minds.com?key=value&otherKey=otherValue';
        $this->stripQueryParameter($fullUrl, 'key')->shouldBe('https://example.minds.com?otherKey=otherValue');
    }

    public function it_should_strip_a_given_query_param_from_a_url_with_one_query_params()
    {
        $fullUrl = 'https://example.minds.com?key=value';
        $this->stripQueryParameter($fullUrl, 'key')->shouldBe('https://example.minds.com');
    }
}
