<?php
namespace Minds\Core\Nostr;

class NIP26DelegateToken
{
    public function __construct(
        protected string $delegatePublicKey,
        protected string $delegatorPublicKey,
        protected string $conditionsQueryString,
        protected string $sig,
    ) {
    }

    public function getDelegatePublicKey(): string
    {
        return $this->delegatePublicKey;
    }

    public function getDelegatorPublicKey(): string
    {
        return $this->delegatorPublicKey;
    }

    public function getConditionsQueryString(): string
    {
        return $this->conditionsQueryString;
    }

    public function getSig(): string
    {
        return $this->sig;
    }

    /**
     * Rebuilds the token that was used to create the signature
     * @return string
     */
    public function getSha256Token(): string
    {
        return hash('sha256', 'nostr:delegation:' . $this->getDelegatePublicKey() . ':' . $this->getConditionsQueryString());
    }

    /**
     * @return array
     */
    public function toTag(): array
    {
        return [
            'delegation',
            $this->delegatorPublicKey,
            $this->conditionsQueryString,
            $this->sig,
        ];
    }
}
