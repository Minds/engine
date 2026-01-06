<?php declare(strict_types=1);

namespace Minds\Core\GraphQL\Validators;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\QuerySecurityRule;

class QuerySize extends QuerySecurityRule
{
    protected int $maxQueryLength;

    /** @throws \InvalidArgumentException */
    public function __construct(int $maxQueryLength)
    {
        $this->maxQueryLength = $maxQueryLength;
        //$this->setMaxQueryLength($maxQueryLength);
    }

    public function getVisitor(QueryValidationContext $context): array
    {
        return $this->invokeIfNeeded(
            $context,
            [
                NodeKind::OPERATION_DEFINITION => [
                    'leave' => function (OperationDefinitionNode $operationDefinition) use ($context): void {
                        error_log('1');
                        // $maxDepth = $this->fieldDepth($operationDefinition);

                        // if ($maxDepth <= $this->maxQueryDepth) {
                        //     return;
                        // }

                        // $context->reportError(
                        //     new Error(static::maxQueryDepthErrorMessage($this->maxQueryDepth, $maxDepth))
                        // );
                    },
                ],
            ]
        );
    }

    protected function isEnabled(): bool
    {
        return $this->maxQueryLength !== self::DISABLED;
    }
}
