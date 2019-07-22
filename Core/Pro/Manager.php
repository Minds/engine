<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Exception;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;

class Manager
{
    /** @var Save */
    protected $saveAction;

    /** @var Delegates\InitializeValuesDelegate */
    protected $initializeValuesDelegate;

    /** @var User */
    protected $user;

    /**
     * Manager constructor.
     * @param Save $saveAction
     * @param Delegates\InitializeValuesDelegate $initializeValuesDelegate
     */
    public function __construct(
        $saveAction = null,
        $initializeValuesDelegate = null
    )
    {
        $this->saveAction = $saveAction ?: new Save();
        $this->initializeValuesDelegate = $initializeValuesDelegate ?: new Delegates\InitializeValuesDelegate();
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActive()
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        return $this->user->isPro();
    }

    /**
     * @param $until
     * @return bool
     * @throws Exception
     */
    public function enable($until)
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $this->user
            ->setProExpires($until);

        $saved = $this->saveAction
            ->setEntity($this->user)
            ->save();

        $this->initializeValuesDelegate
            ->onEnable($this->user);

        return (bool) $saved;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function disable()
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        // TODO: Disable subscription instead, let Pro expire itself at the end of the sub

        $this->user
            ->setProExpires(0);

        $saved = $this->saveAction
            ->setEntity($this->user)
            ->save();

        return (bool) $saved;
    }
}
