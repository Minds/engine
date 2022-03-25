<?php
namespace Minds\Core\DismissibleNotices;

use Minds\Core\Session;
use Minds\Core\Entities\Actions;
use Minds\Exceptions\UserErrorException;

/**
 * DismissibleNotice Manager - handles the submission of notice dismissals.
 */
class Manager
{
    /** @var string[] whitelist of dismissible notice IDs */
    const NOTICE_IDS = [
        'build-your-algorithm',
        'enable-push-notifications'
    ];

    /** @var User */
    protected $user;

    /** @var Actions\Save */
    protected $save;

    public function __construct(
        $user = null,
        $save = null
    ) {
        $this->user = $user ?? Session::getLoggedInUser();
        $this->save = $save ?? new Actions\Save();
    }

    /**
     * Adds notice to list of dismissed notices.
     * @param string $id - id of notice to dismiss.
     * @return bool - whether the notice dismissal was successfully saved to user object.
     */
    public function setDismissed(string $id): bool
    {
        if (!in_array($id, static::NOTICE_IDS, true)) {
            throw new UserErrorException('Invalid Notice ID provided');
        }

        $notices = $this->user->getDismissedNotices();

        if ($this->isNoticeAlreadyDismissed($notices, $id)) {
            return false;
        }

        $notices[] = [
            'id' => $id,
            'timestamp_ms' => time() * 1000
        ];

        $this->user->setDismissedNotices($notices);

        return $this->save
            ->setEntity($this->user)
            ->save();
    }

    /**
     * Whether notice is already dismissed.
     * @param array $id - array of a users dismissed notices.
     * @param string $id - id of notice to check.
     * @return bool - whether the notice has already been dismissed.
     */
    private function isNoticeAlreadyDismissed(array $notices, string $id): bool
    {
        $matches = array_filter($notices, function ($notice) use ($id) {
            return $notice['id'] === $id;
        });
        return count($matches) > 0;
    }
}
