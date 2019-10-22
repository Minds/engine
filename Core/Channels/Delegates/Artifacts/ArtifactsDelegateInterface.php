<?php
/**
 * ArtifactsDelegateInterface.
 *
 * @author emi
 */

namespace Minds\Core\Channels\Delegates\Artifacts;
use Minds\Entities\User;

interface ArtifactsDelegateInterface
{
    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function snapshot($userGuid) : bool;

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function restore($userGuid) : bool;

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function hide($userGuid) : bool;

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function delete($userGuid) : bool;

     /**
     * @param User $user
     * @return bool
     */
    public function updateOwnerObject($userGuid, array $value) : bool;
}
