<?php
declare(strict_types=1);

namespace Minds\Core\Entities\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The AttachmentNode returns relevant information about an attachment.
 */
#[Type]
class AttachmentNode
{
    public function __construct(
        #[Field] public readonly string $guid,
        #[Field] public readonly string $containerGuid,
        #[Field] public readonly string $type,
        #[Field] public readonly string $src,
        #[Field] public readonly string $href,
        #[Field] public readonly ?bool $mature,
        #[Field] public readonly ?int $width,
        #[Field] public readonly ?int $height,
    ) {}

    #[Field]
    public function getId(): ID
    {
        return new ID($this->containerGuid . '-'. $this->guid);
    }
}
