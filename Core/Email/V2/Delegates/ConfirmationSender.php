<?php
/**
 * ConfirmationSender
 *
 * @author edgebal
 */

namespace Minds\Core\Email\V2\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Recurring\Confirmation\Confirmation as ConfirmationEmail;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;
use Minds\Interfaces\SenderInterface;

/**
 * Responsible for sending initial link-based email confirmation emails.
 */
class ConfirmationSender implements SenderInterface
{
    /**
     * Constructor.
     * @param ?ConfirmationEmail $confirmation - confirmation email class
     * @param ?ExperimentsManager $experiments - experiments manager.
     */
    public function __construct(
        private ?ConfirmationEmail $confirmation = null,
        private ?ExperimentsManager $experiments = null
    ) {
        $this->confirmation ??= new ConfirmationEmail();
        $this->experiments ??= Di::_()->get('Experiments\Manager');
    }

    /**
     * Send confirmation email.
     * @param User $user - user to send to.
     * @return void
     */
    public function send(User $user): void
    {
        // if experiment is active, email will be triggered by MFA modal.
        if (!$this->isEmailCodeExperimentActive($user)) {
            $this->confirmation->setUser($user)
            ->send();
        }
    }

    /**
     * True if experiment is active to send users a code instead of a link.
     * @param User $user - user to check experiment state for.
     * @return boolean - true if experiment is active.
     */
    private function isEmailCodeExperimentActive(User $user): bool
    {
        return $this->experiments->setUser($user)
            ->isOn('minds-3055-email-codes') &&
            !isset($_SERVER['HTTP_APP_VERSION']);
    }
}
