<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Onboarding\V5\GraphQL\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\OnboardingState::class,
            Types\OnboardingStepProgressState::class
        ]));
    }
}
