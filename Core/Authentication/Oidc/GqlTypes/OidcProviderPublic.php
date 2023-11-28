<?php
namespace Minds\Core\Authentication\Oidc\GqlTypes;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class OidcProviderPublic
{
    public function __construct(private OidcProvider $oidcProvider, private Config $config)
    {
    }

    #[Field]
    public function getId(): int
    {
        return $this->oidcProvider->id;
    }

    #[Field]
    public function getName(): string
    {
        return $this->oidcProvider->name;
    }

    #[Field]
    public function getLoginUrl(): string
    {
        return $this->config->get('site_url') . 'api/v3/authenticate/oidc/login?providerId=' . $this->oidcProvider->id;
    }
}
