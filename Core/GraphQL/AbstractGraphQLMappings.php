<?php declare(strict_types=1);
/**
 * ModuleRoutes
 * @author edgebal
 */

namespace Minds\Core\GraphQL;

use Minds\Core\Di\Di;
use TheCodingMachine\GraphQLite\SchemaFactory;

abstract class AbstractGraphQLMappings
{
    /**
     * Extend this class in your modules to register types to be used via graphql
     */
    public function __construct(
        protected ?SchemaFactory $schemaFactory = null,
    ) {
        $this->schemaFactory ??= Di::_()->get(SchemaFactory::class);
    }

    /**
     * Enter your registers inside this function and call from your Module class like GraphQLMappings->register();
     */
    abstract public function register(): void;
}
