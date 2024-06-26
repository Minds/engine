<?php

namespace Spec\Minds\Core\MultiTenant\Configs\Validators;

use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfigInput;
use Minds\Core\MultiTenant\Configs\Validators\MultiTenantConfigInputValidator;
use PhpSpec\ObjectBehavior;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class MultiTenantConfigInputValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(MultiTenantConfigInputValidator::class);
    }

    public function it_should_be_enabled()
    {
        $this->isEnabled()->shouldBe(true);
    }

    public function it_should_validate_a_valid_input()
    {
        $siteName = 'Test site';
        $primaryColor = '#fff000';
        $replyEmail = 'some@email.com';

        $this->validate(new MultiTenantConfigInput(
            siteName: $siteName,
            primaryColor: $primaryColor,
            replyEmail: $replyEmail
        ))->shouldBe(null);
    }

    public function it_should_NOT_validate_an_input_with_too_short_a_site_name()
    {
        $siteName = '12';
        $primaryColor = '#fff000';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: $primaryColor
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_too_long_a_site_name()
    {
        $siteName = '123456789012345678901234567890123456789012345678901';
        $primaryColor = '#fff000';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: $primaryColor
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_primary_color_not_starting_with_hash()
    {
        $siteName = 'Test site';
        $primaryColor = 'fff000';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: $primaryColor
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_primary_color_of_invalid_length()
    {
        $siteName = 'Test site';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: '#1'
            )
        );

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: '#1111'
            )
        );

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: '#1111111'
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_primary_color_of_invalid_hex()
    {
        $siteName = 'Test site';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                primaryColor: '#abcdez'
            )
        );
    }

    public function it_should_NOT_validate_an_input_with_an_invalid_reply_email_address()
    {
        $siteName = 'Test site';
        $replyEmail = 'some.email@someemail';

        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                siteName: $siteName,
                replyEmail: $replyEmail
            )
        );
    }

    public function it_should_validate_a_valid_max_length_custom_home_page_description()
    {
        $this->validate(new MultiTenantConfigInput(
            customHomePageDescription: str_repeat('a', 160),
        ))->shouldBe(null);
    }

    public function it_should_validate_a_valid_empty_custom_home_page_description()
    {
        $this->validate(new MultiTenantConfigInput(
            customHomePageDescription: ''
        ))->shouldBe(null);
    }

    public function it_should_NOT_validate_an_input_with_too_long_a_custom_home_page_description()
    {
        $this->shouldThrow(GraphQLException::class)->duringValidate(
            new MultiTenantConfigInput(
                customHomePageDescription: str_repeat('a', 161),
            )
        );
    }
}
