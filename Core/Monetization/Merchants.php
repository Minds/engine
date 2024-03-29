<?php
namespace Minds\Core\Monetization;

use Minds\Core;
use Minds\Entities;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;

class Merchants
{
    /** @var Entities\User */
    private $user;

    public function __construct(
        protected ?Save $save = null,
    ) {
        $this->save ??= new Save();
    }

    public function setUser($user)
    {
        if (!is_object($user)) {
            $user = new Entities\User($user);
        }

        if (!$user || !$user->guid) {
            throw new \Exception('Invalid user');
        }

        $this->user = $user;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function refreshUser()
    {
        if (!$this->user) {
            return;
        }

        $this->setUser(new Entities\User($this->user->guid, false));
    }

    public function getId()
    {
        if (!$this->user) {
            throw new \Exception('No user');
        }

        if ($this->user->ban_monetization === 'yes') {
            return false;
        }

        $merchant = $this->user->getMerchant();

        if (!$merchant || $merchant['service'] !== 'stripe') {
            return false;
        }

        return $merchant['id'];
    }

    public function ban()
    {
        if (!$this->user) {
            throw new \Exception('No user');
        }

        $this->user->ban_monetization = 'yes';

        $this->save->setEntity($this->user)->withMutatedAttributes(['ban_monetization'])->save();

        return true;
    }

    public function unban()
    {
        if (!$this->user) {
            throw new \Exception('No user');
        }

        $this->user->ban_monetization = 'no';

        $this->save->setEntity($this->user)->withMutatedAttributes(['ban_monetization'])->save();

        return true;
    }

    public function isBanned()
    {
        return $this->user->ban_monetization === 'yes';
    }
}
