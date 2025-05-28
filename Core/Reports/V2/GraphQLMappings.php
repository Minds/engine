<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\Reports\V2\Types\Report;
use Minds\Core\Reports\V2\Types\ReportEdge;
use Minds\Core\Reports\V2\Types\ReportInput;
use Minds\Core\Reports\V2\Types\ReportsConnection;
use Minds\Core\Reports\V2\Types\VerdictInput;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Reports\V2\Controllers');
        $this->schemaFactory->addNamespace('Minds\\Core\\Reports\\Enums');

        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Report::class,
            ReportInput::class,
            VerdictInput::class,
            ReportEdge::class,
            ReportsConnection::class
        ]));

        $this->schemaFactory->setInputTypeValidator(new Validators\ReportInputValidator());
    }
}
