<?php

namespace Spec\Minds\Core\DID\Keypairs;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\DID\Keypairs\DIDKeypair;
use Minds\Core\DID\Keypairs\Repository;
use PhpSpec\ObjectBehavior;

class RepositorySpec extends ObjectBehavior
{
    protected $cql;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_keypair_to_database(DIDKeypair $keypair)
    {
        $this->add($keypair);
    }

    public function it_should_return_keypair_from_database()
    {
        $this->get('123');
    }
}
