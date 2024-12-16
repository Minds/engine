<?php
namespace Minds\Core\Search\Enums;

use Minds\Core\Feeds\Elastic\V2\Enums\MediaTypeEnum;
use OpenApi\Annotations\MediaType;
use TheCodingMachine\GraphQLite\Annotations\Input;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Input()]
enum SearchMediaTypeEnum
{
    case ALL;
    case IMAGE;
    case VIDEO;
    case BLOG;
    case AUDIO;

    /**
     * Helper function to map the enum to its respective MediaTypeEnum
     */
    public static function toMediaTypeEnum(SearchMediaTypeEnum $enum): MediaTypeEnum
    {
        return match ($enum) {
            SearchMediaTypeEnum::ALL => MediaTypeEnum::ALL,
            SearchMediaTypeEnum::BLOG => MediaTypeEnum::BLOG,
            SearchMediaTypeEnum::VIDEO => MediaTypeEnum::VIDEO,
            SearchMediaTypeEnum::IMAGE => MediaTypeEnum::IMAGE,
            SearchMediaTypeEnum::AUDIO => MediaTypeEnum::AUDIO,
        };
    }
}
