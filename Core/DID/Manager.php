<?php
namespace Minds\Core\DID;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class Manager
{
    public function __construct(
        protected ?Config $config = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Keypairs\Manager $keypairsManager = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->keypairsManager ??= new Keypairs\Manager();
    }

    /**
     * Returns a DID id
     * @param User $user (optional)
     * @return string
     */
    public function getId(?User $user = null): string
    {
        $domain = $this->getDomain();

        $id = "did:web:$domain";

        if ($user) {
            $username = $user->getUsername();
            return "$id:$username";
        } else {
            return $id;
        }
    }

    /**
     * This is the root document for the site
     * @return DIDDocument
     */
    public function getRootDocument(): DIDDocument
    {
        $domain = $this->getDomain();
        
        $document = new DIDDocument();
        $document->setId("did:web:$domain");
    
        return $document;
    }

    /**
     * This is a document for a user
     * If a keypair doesn't exist, it will be created
     * @param string $username
     * @return DIDDocument
     */
    public function getUserDocument(string $username): DIDDocument
    {
        $domain = $this->getDomain();

        $user = $this->entitiesBuilder->getByUserByIndex(strtolower($username));

        if (!$user) {
            throw new NotFoundException();
        }

        $id = "did:web:$domain:" . $user->getUsername();

        $keypair = $this->keypairsManager->getKeypair($user);

        if (!$keypair) {
            $keypair = $this->keypairsManager->createKeypair($user);
            $this->keypairsManager->add($keypair);
        }

        $document = new DIDDocument();
        $document
            ->setId($id)
            ->setVerificiationMethod([
                [
                    "id" => $id . "#key-1",
                    "type" => "Ed25519VerificationKey2020",
                    "controller" => $id,
                    "publicKeyMultibase" => $this->keypairsManager->getMultibase($this->keypairsManager->getPublicKey($keypair)),
                ]
            ])
            ->setAuthentication([ $id . "#key-1", ]);

        return $document;
    }

    /**
     * @return array
     */
    protected function getConfig(): array
    {
        return  $this->config->get('did') ?? [
            'domain' => '',
        ];
    }

    /**
     * https://w3c-ccg.github.io/did-method-web/#method-specific-identifier
     * A port MAY be included and the colon MUST be percent encoded to prevent a conflict with paths
     * @return string
     */
    protected function getDomain(): string
    {
        return urlencode($this->getConfig()['domain']);
    }
}
