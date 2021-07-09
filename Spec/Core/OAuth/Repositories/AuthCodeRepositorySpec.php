<?php

namespace Spec\Minds\Core\OAuth\Repositories;

use DateTime;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Minds\Core\OAuth\Repositories\AuthCodeRepository;
use Minds\Core\Data\Cassandra\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class AuthCodeRepositorySpec extends ObjectBehavior
{
    /** @var Client $client */
    private $client;

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AuthCodeRepository::class);
    }

    public function it_should_add_new_auth_code(AuthCodeEntityInterface $authCodeEntity, ClientEntityInterface $clientEntity)
    {
        $authCodeEntity->getIdentifier()
            ->willReturn('codeId');

        $authCodeEntity->getClient()
            ->willReturn($clientEntity);

        $clientEntity->getIdentifier()
            ->willReturn('phpspec');

        $authCodeEntity->getUserIdentifier()
            ->willReturn('063');

        $authCodeEntity->getExpiryDateTime()
            ->willReturn(new DateTime());

        $this->client->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0] === 'codeId'
                && $values[1] === 'phpspec'
                && $values[2]->value() === '063'
                && $values[3]->time() === time();
        }))
            ->willReturn(true);

        $this->persistNewAuthCode($authCodeEntity);
    }

    public function it_should_revoke_code()
    {
        $this->client->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0] === 'codeId';
        }))
            ->willReturn(true);

        $this->revokeAuthCode('codeId');
    }

    public function it_should_return_code_as_revoked()
    {
        $this->isAuthCodeRevoked('codeId')
            ->shouldBe(true);
    }

    public function it_should_return_code_as_not_revoked(AuthCodeEntityInterface $authCodeEntity)
    {
        $this->client->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0] === 'codeId';
        }))
            ->willReturn(new Rows([['code_id' => '123']], ''));

        $this->isAuthCodeRevoked('codeId')
            ->shouldBe(false);
    }
}
