<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Validators;

use Minds\Core\MultiTenant\Types\MultiTenantCustomHostname;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

class MultiTenantCustomHostnameInputValidator implements InputTypeValidatorInterface
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
     */
    public function validate(object $input): void
    {
        if (!($input instanceof MultiTenantCustomHostname)) {
            return;
        }

        if ($input->hostname) {
            // validate domain is valid.
            if (!filter_var($input->hostname, FILTER_VALIDATE_DOMAIN)) {
                throw new GraphQLException("Invalid hostname provided", 400, null, "Validation", ['field' => 'hostname']);
            }
        }
    }
}
