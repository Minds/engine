<?php
namespace Minds\Core\DID\UniResolver;

class Manager
{
    public function __construct(protected ?Client $client = null)
    {
        $this->client ??= new Client();
    }
    
    /**
     * @param string $did
     * @return array - in the future consider if DIDDocument can be used
     */
    public function resolve(string $did): ?array
    {
        return $this->client->request('/1.0/identifiers/' . $did);
    }
}
