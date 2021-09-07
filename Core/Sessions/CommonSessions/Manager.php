<?php
/**
 * Minds Session Manager
 */

namespace Minds\Core\Sessions\CommonSessions;

use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Core\OAuth\Managers\AccessTokenManager as OAuthManager;
use Minds\Entities\User;
use Exception;
use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\Sessions\Session;
use Minds\Exceptions\UserErrorException;
use Minds\Core;

class Manager
{
    /** @var string */
    const PLATFORM_BROWSER = 'browser';

    /** @var string */
    const PLATFORM_APP = 'app';

    /** @var string */
    const PLATFORM_CHAT = 'matrix';

    /** @var SessionsManager */
    private $sessionsManager;

    /** @var OAuthManager */
    private $oauthManager;

    public function __construct(
        $sessionsManager = null,
        $oauthManager = null
    ) {
        $this->sessionsManager = $sessionsManager ?: new SessionsManager;
        $this->oauthManager = $oauthManager ?: new OAuthManager;
    }

    /**
     * Gets all common sessions for a user
     * @param User $user
     * @return CommonSession[]
     */
    public function getAll(User $user): array
    {
        // Get the JWT sessions

        $jwtSessions = $this->sessionsManager->getList($user);

        $jwtSessions = array_map(function (Session $jwtSession) {
            // Build the common session here
            $commonSession = new CommonSession();

            $commonSession
                ->setId($jwtSession->getId())
                ->setUserGuid($jwtSession->getUserGuid())
                ->setExpires($jwtSession->getExpires())
                ->setIp($jwtSession->getIp())
                ->setLastActive($jwtSession->getLastActive())
                ->setPlatform(self::PLATFORM_BROWSER);

            return $commonSession;
        }, $jwtSessions);

        // Get the OAuth sessions

        $oauthSessions = $this->oauthManager->getList($user);

        $oauthSessions = array_map(function (AccessTokenEntity $oauthSession) {
            // Build the common session here
            $commonSession = new CommonSession();

            $platform = null;
            switch ($oauthSession->getClient()->getIdentifier() ?? '') {
                case 'matrix':
                    $platform = self::PLATFORM_CHAT;
                    break;
                case 'mobile':
                default:
                    $platform = self::PLATFORM_APP;
            }

            $commonSession
                ->setId($oauthSession->getIdentifier())
                ->setUserGuid($oauthSession->getUserIdentifier())
                ->setExpires($oauthSession->getExpiryDateTime()->getTimestamp())
                ->setIp($oauthSession->getIp())
                ->setLastActive($oauthSession->getLastActive())
                ->setPlatform($platform);

            return $commonSession;
        }, $oauthSessions);

        // Combine arrays and remove expired
        $sessions = array_filter(array_merge($jwtSessions, $oauthSessions), function (CommonSession $session) {
            return $session->getExpires() > time() - (86400 * 30); // Show also expired within last 30 days
        });

        // Sort by last active
        usort($sessions, function (CommonSession $a, CommonSession $b) {
            return $a->getLastActive() < $b->getLastActive() ? 1 : 0;
        });

        return $sessions;
    }

    /**
     * Delete a session
     * @param CommonSession $commonSession
     * @return bool
     */
    public function delete(CommonSession $commonSession)
    {
        $platform = $commonSession->getPlatform();

        if ($platform === self::PLATFORM_BROWSER) {
            $session = new Session();
            $session
                ->setUserGuid($commonSession->getUserGuid())
                ->setId($commonSession->getId());

            $response = $this->sessionsManager->delete($session);
        } elseif ($platform === self::PLATFORM_APP || $platform === self::PLATFORM_CHAT) {
            $token = new AccessTokenEntity();
            $token->setIdentifier($commonSession->getId());
            $token->setUserIdentifier($commonSession->getUserGuid());
            $response = $this->oauthManager->delete($token);
        } else {
            throw new UserErrorException('Invalid session type');
        }

        if (!$response) {
            throw new UserErrorException("Could not delete session");
        }

        return $response;
    }

    /**
     * Delete all sessions
     * @param User $user
     * @return bool
     */
    public function deleteAll(User $user)
    {
        if (!$user) {
            $user = Core\Session::getLoggedInUser();
        }

        $sessionsResponse = $this->sessionsManager->deleteAll($user);

        $oauthResponse = $this->oauthManager->deleteAll($user);

        return $sessionsResponse && $oauthResponse;
    }
}
