<?php
namespace Minds\Core\GraphQL;

use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Minds\Core\Di\Di;
use TheCodingMachine\GraphQLite\Context\Context;

class Controller
{
    public function exec(ServerRequest $request): JsonResponse
    {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $query = $input['query'];
        $variableValues = isset($input['variables']) ? $input['variables'] : null;

        $schema = Di::_()->get(Schema::class);

        $result = GraphQL::executeQuery($schema, $query, null, new Context(), $variableValues);
        $output = $result->toArray();

        if ($result->errors) {
            foreach ($result->errors as $error) {
                error_log($error->getMessage());
            }
        }

        return new JsonResponse($output);
    }
}
