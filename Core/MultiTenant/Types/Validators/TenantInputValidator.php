<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Validators;

use Minds\Core\MultiTenant\Models\Tenant;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

class TenantInputValidator implements InputTypeValidatorInterface
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
        if (!($input instanceof Tenant)) {
            return;
        }

        if ($input->domain) {
            // validate domain is valid.
            if (!filter_var($input->domain, FILTER_VALIDATE_DOMAIN)) {
                throw new GraphQLException("Invalid domain provided", 400, null, "Validation", ['field' => 'domain']);
            }
        }
    }
}
