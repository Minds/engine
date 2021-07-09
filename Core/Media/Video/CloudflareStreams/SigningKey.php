<?php
namespace Minds\Core\Media\Video\CloudflareStreams;

use Minds\Traits\MagicAttributes;

/**
 * @method string getId()
 * @method SigningKey setId(string $id)
 * @method string getPem()
 * @method SigningKey setPem(string $pem)
 */
class SigningKey
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $pem;
}
