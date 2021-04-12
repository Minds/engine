<?php

namespace Minds\Entities;

use Minds\Core;
use Minds\Helpers;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Di\Di;

/**
 * User Entity.
 *
 * @todo Do not inherit from ElggUser
 */
class Channel extends User
{
    public $isAdmin;
    public $isLoggedIn;
    public $isOwner;
    public $isPublic;

    public $test = false;


    protected function initializeAttributes()
    {
        parent::initializeAttributes();
        $this->isAdmin = Core\Session::isAdmin();
        $this->isLoggedIn = Core\Session::isLoggedin();
        $this->isOwner = $isLoggedIn && ((string) Core\Session::getLoggedinUser()->guid === (string) $this->guid);
        $this->isPublic = $isLoggedIn && $this->isPublicDateOfBirth();
    }

    public function export()
    {
        $export = parent::export();

        $export['avatar_url'] = [
            'tiny' => $this->getIconURL('tiny'),
            'small' => $this->getIconURL('small'),
            'medium' => $this->getIconURL('medium'),
            'large' => $this->getIconURL('large'),
            'master' => $this->getIconURL('master')
        ];

        $export['briefdescription'] = $this->briefdescription ?: '';
        $export['city'] = $this->city ?: "";
        $export['gender'] = $this->gender ?: "";

        if (
            $this->getDateOfBirth() &&
            (
                $this->isAdmin ||
                $this->isOwner ||
                $this->isPublic
            )
        ) {
            $export['dob'] = $this->getDateOfBirth();
        }

        if (
            $this->isAdmin ||
            $this->isOwner
        ) {
            $export['public_dob'] = $this->isPublicDateOfBirth();
        }

        $carousels = Core\Entities::get(['subtype'=>'carousel', 'owner_guid'=>$this->guid]);
        if ($carousels) {
            foreach ($carousels as $carousel) {
                $export['carousels'][] = [
                  'guid' => (string) $carousel->guid,
                  'top_offset' => $carousel->top_offset,
                  'src'=> Core\Config::_()->cdn_url . "fs/v1/banners/$carousel->guid/fat/$carousel->last_updated"
                ];
            }
        }

        // The 'blocked' exported field tells the current logged in user that they have BLOCKED
        // said user, not that they are blocked. We use 'hasBlocked' vs 'isBlocked' to get
        // the inversion
        if (Core\Session::getLoggedInUser()) {
            $blockEntry = (new BlockEntry)
                ->setActor(Core\Session::getLoggedInUser())
                ->setSubject($this);
            $hasBlocked = Di::_()->get('Security\Block\Manager')->hasBlocked($blockEntry);
            $isBlocked = Di::_()->get('Security\Block\Manager')->isBlocked($blockEntry);
            $export['blocked'] = $hasBlocked;
            $export['blocked_by'] = $isBlocked;
        }

        if ($this->isPro()) {
            /** @var Core\Pro\Manager $manager */
            $manager = Core\Di\Di::_()->get('Pro\Manager');
            $manager
                ->setUser($this);

            $proSettings = $manager->get();

            if ($proSettings) {
                $export['pro_settings'] = $proSettings;
            }
        }

        return $export;
    }

    /**
     * Returns an array of which Entity attributes are exportable.
     *
     * @return array
     */
    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), []);
    }

    public function requireLogin()
    {
        return !$this->isLoggedIn && Di::_()->get('Blockchain\Wallets\Balance')
            ->setUser($user)
            ->count() === 0;
    }
}
