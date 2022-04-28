<?php
namespace Minds\Core\DID\Keypairs;

use Minds\Traits\MagicAttributes;

/**
 * @method string getUserGuid()
 * @method self setUserGuid(string $userGuid)
 * @method string getKeypair()
 * @method self setKeypair(string $keypair)
 */
class DIDKeypair
{
    use MagicAttributes;

    protected string $userGuid;

    protected string $keypair;
}
