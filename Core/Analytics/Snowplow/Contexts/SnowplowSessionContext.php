<?php
namespace Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Traits\MagicAttributes;

/**
 * @method SnowplowActionEvent setLoggedIn(bool $loggedIn)
 * @method SnowplowActionEvent setUserPhoneNumberHash(string $userPhoneNumberHash)
 */
class SnowplowSessionContext implements SnowplowContextInterface
{
    use MagicAttributes;

    /** @var bool */
    protected $loggedIn = true;

    /** @var string */
    protected $userPhoneNumberHash;

    /**
     * Returns the schema
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/session_context/jsonschema/1-0-0";
    }

    /**
     * Returns the sanitized data
     * null values are removed
     * @return array
     */
    public function getData(): array
    {
        return array_filter([
            'logged_in' => $this->loggedIn,
            'user_phone_number_hash' => $this->userPhoneNumberHash,
        ]);
    }
}
