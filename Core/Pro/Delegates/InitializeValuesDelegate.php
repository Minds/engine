<?php
/**
 * InitializeValuesDelegate
 * @author edgebal
 */

namespace Minds\Core\Pro\Delegates;

use Exception;
use Minds\Core\Pro\Repository;
use Minds\Core\Pro\Values;
use Minds\Entities\User;

class InitializeValuesDelegate
{
    /** @var Repository */
    protected $repository;

    /**
     * InitializeValuesDelegate constructor.
     * @param Repository $repository
     */
    public function __construct(
        $repository = null
    )
    {
        $this->repository = $repository ?: new Repository();
    }

    /**
     * @param User $user
     * @throws Exception
     */
    public function onEnable(User $user)
    {
        $values = $this->repository
            ->getList(['user_guid' => $user->guid])
            ->toArray()[0] ?? null;

        if (!$values) {
            $values = new Values();
            $values
                ->setUserGuid($user->guid);
        }

        if (!$values->getDomain()) {
            $values->setDomain("pro-{$user->guid}.minds.com");
        }

        $this->repository
            ->add($values);
    }
}
