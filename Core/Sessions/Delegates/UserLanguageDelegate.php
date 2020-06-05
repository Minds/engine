<?php
namespace Minds\Core\Sessions\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\I18n\Manager;
use Minds\Entities\User;

/**
 * I18n-derived user language delegate
 * @package Minds\Core\Sessions\Delegates
 */
class UserLanguageDelegate
{
    /** @var Manager */
    protected $i18n;

    /**
     * UserLanguageDelegate constructor.
     * @param $i18n
     */
    public function __construct(
        $i18n = null
    ) {
        $this->i18n = $i18n ?: Di::_()->get('I18n\Manager');
    }

    /**
     * Sets the language cookie
     * @param User $user
     */
    public function setCookie(User $user): void
    {
        $this->i18n->setLanguageCookie($user->getLanguage() ?: Manager::DEFAULT_LANGUAGE);
    }
}
