<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\AbstractType;

class PublicKeyType extends AbstractType
{
    public function __construct(
        #[ExportProperty]
        public string $id,
        #[ExportProperty]
        public string $owner,
        #[ExportProperty]
        public string $publicKeyPem,
    ) {

    }

}
