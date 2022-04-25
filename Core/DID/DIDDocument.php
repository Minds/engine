<?php
namespace Minds\Core\DID;

use Minds\Entities\ExportableInterface;

class DIDDocument implements ExportableInterface
{
    protected string $id = "";
    protected array $verificationMethod = [];
    protected array $authentication = [];

    /**
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * TODO: Bring type safety into here instead of using array
     * @param array $verificationMethod
     * @return self
     */
    public function setVerificiationMethod(array $verificationMethod): self
    {
        $this->verificationMethod = $verificationMethod;
        return $this;
    }

    /**
     * TODO: Bring type safety into here instead of using array
     * @param string[] $authentication
     * @return self
     */
    public function setAuthentication(array $authentication): self
    {
        $this->authentication = $authentication;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function export(array $extras = []): array
    {
        $export = [
            "@context" => "https://www.w3.org/ns/did/v1",
            "id" => $this->id,
        ];

        if ($this->verificationMethod) {
            $export['verificationMethod'] = $this->verificationMethod;
        }

        if ($this->authentication) {
            $export['authentication'] = $this->authentication;
        }

        return $export;
    }
}
