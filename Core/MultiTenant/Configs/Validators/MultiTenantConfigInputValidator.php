<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Validators;

use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfigInput;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;
use Minds\Helpers\Validation;

/**
 * Multi-tenant config input validator. Validates input for multi-tenant config
 * before input is passed to the controller.
 */
class MultiTenantConfigInputValidator implements InputTypeValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * @throws GraphQLException
     */
    public function validate(object $input): void
    {
        if (!($input instanceof MultiTenantConfigInput)) {
            return;
        }

        if (isset($input->siteName) && (
            mb_strlen($input->siteName) < 3 ||
            mb_strlen($input->siteName) > 50
        )) {
            throw new GraphQLException("Network name must be between 3 and 50 characters", 400, null, "Validation", ['field' => 'networkName']);
        }

        if (isset($input->primaryColor)) {
            // validate hex colour starts with a #.
            if (!str_starts_with($input->primaryColor, '#')) {
                throw new GraphQLException("Primary color must start with #", 400, null, "Validation", ['field' => 'primaryColor']);
            }
            // validate length of hex colour is valid.
            if (!in_array(strlen($input->primaryColor), [4, 7, 9], true)) {
                throw new GraphQLException("Invalid primary color length", 400, null, "Validation", ['field' => 'primaryColor']);
            }
            // validate the value is valid hex.
            if (!ctype_xdigit(substr($input->primaryColor, 1))) {
                throw new GraphQLException("Invalid hex value", 400, null, "Validation", ['field' => 'primaryColor']);
            }
        }

        if (isset($input->replyEmail) && $input->replyEmail !== '' && !Validation::isValidEmail($input->replyEmail)) {
            throw new GraphQLException("Invalid reply-to email address", 400, null, "Validation", ['field' => 'replyEmail']);
        }

        if (isset($input->customHomePageDescription) && mb_strlen($input->customHomePageDescription) > 160) {
            throw new GraphQLException("Custom home page description must be less than 160 characters", 400, null, "Validation", ['field' => 'customHomePageDescription']);
        }

        return;
    }
}
