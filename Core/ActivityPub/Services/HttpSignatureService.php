<?php
namespace Minds\Core\ActivityPub\Services;

use HttpSignatures\SignatureParametersParser;

class HttpSignatureService
{
    public function getKeyId(string $header): string
    {
        $sigParser = new SignatureParametersParser($header);
        $parsed  = $sigParser->parse();
        return $parsed['keyId'];
    }
}
