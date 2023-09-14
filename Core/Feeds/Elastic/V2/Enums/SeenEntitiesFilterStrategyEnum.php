<?php
namespace Minds\Core\Feeds\Elastic\V2\Enums;

use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input()]
enum SeenEntitiesFilterStrategyEnum
{
    /**
     * This should demote posts already seen by rescoring the weights
     */
    case DEMOTE;

    /**
     * Will exclude entities already seen
     */
    case EXCLUDE;

    /**
     * Don't do anything
     */
    case NOOP;
}
