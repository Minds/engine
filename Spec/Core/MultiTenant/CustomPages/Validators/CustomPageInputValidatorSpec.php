<?php

namespace Spec\Minds\Core\MultiTenant\CustomPages\Validators;

use Minds\Core\MultiTenant\CustomPages\Types\CustomPageInput;
use Minds\Core\MultiTenant\CustomPages\Validators\CustomPageInputValidator;
use PhpSpec\ObjectBehavior;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CustomPageInputValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CustomPageInputValidator::class);
    }

    public function it_should_be_enabled()
    {
        $this->isEnabled()->shouldBe(true);
    }

    public function it_should_validate_a_valid_input()
    {
        $pageType = 1; // Valid page type
        $externalLink = 'https://example.com';

        $this->validate(new CustomPageInput(
            pageType: $pageType,
            content: null,
            externalLink: $externalLink
        ))->shouldBe(null);
    }

    public function it_should_validate_a_valid_input_with_no_external_link_or_content()
    {
        $pageType = 1; // Valid page type

        $this->validate(new CustomPageInput(
            pageType: $pageType,
            content: null,
            externalLink: null,
        ))->shouldBe(null);
    }

    public function it_should_NOT_validate_an_input_with_external_link_too_long()
    {
        $pageType = 1; // Valid page type
        $externalLink = str_repeat('a', 2001); // Longer than 2000 characters

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new CustomPageInput(
                pageType: $pageType,
                content: null,
                externalLink: $externalLink
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_content_too_long()
    {
        $pageType = 1; // Valid page type
        $content = str_repeat('a', 65001); // Longer than 65000 characters

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new CustomPageInput(
                pageType: $pageType,
                content: $content,
                externalLink: null
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_both_content_and_external_link()
    {
        $pageType = 1; // Valid page type
        $externalLink = 'https://example.com';
        $content = 'Valid content';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new CustomPageInput(
                pageType: $pageType,
                content: $content,
                externalLink: $externalLink,
            )
        );
    }
}
