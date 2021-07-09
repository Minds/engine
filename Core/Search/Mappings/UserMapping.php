<?php

/**
 * Mapping for user documents
 *
 * @author emi
 */

namespace Minds\Core\Search\Mappings;

use Minds\Exceptions\BannedException;
use Minds\Helpers\Flags;

class UserMapping extends EntityMapping implements MappingInterface
{
    /**
     * UserMapping constructor.
     */
    public function __construct()
    {
        $this->mappings = array_merge($this->mappings, [
            'username' => [ 'type' => 'text', '$exportField' => 'username' ],
            'briefdescription' => [ 'type' => 'text', '$exportField' => 'briefdescription' ],
            'group_membership' => [ 'type' => 'text' ],
            'email_confirmed_at' => [ 'type' => 'date', '$exportField' => 'email_confirmed_at' ],
            'suggest' => [ 'type' => 'completion' ]
        ]);
    }

    /**
     * @param array $defaultValues
     * @return array
     */
    public function map(array $defaultValues = [])
    {
        $map = parent::map($defaultValues);

        //if (isset($map['tags'])) {
        //    unset($map['tags']);
        //}

        if (Flags::shouldFail($this->entity)) {
            throw new BannedException('User is banned');
        }

        if (method_exists($this->entity, 'isMature')) {
            $map['mature'] = $this->entity->isMature();
        } else {
            $map['mature'] = false;
        }

        if (method_exists($this->entity, 'getGroupMembership')) {
            $map['group_membership'] = array_values($this->entity->getGroupMembership());
        } else {
            $map['group_membership'] = [];
        }

        if (method_exists($this->entity, 'getEmailConfirmedAt')) {
            $map['email_confirmed_at'] = $this->entity->getEmailConfirmedAt() * 1000;
        }

        if ($this->entity->getProExpires()) {
            $map['pro_expires'] = $this->entity->getProExpires() * 1000;
        }

        if ($this->entity->getPlusExpires()) {
            $map['plus_expires'] = $this->entity->getPlusExpires() * 1000;
        }

        $map['tags'] = array_values(array_unique($this->entity->getTags()));

        return $map;
    }

    /**
     * @param array $defaultValues
     * @return array
     */
    public function suggestMap(array $defaultValues = [])
    {
        $map = parent::suggestMap($defaultValues);

        $name = $this->entity->getName();
        $username = $this->entity->getUsername();

        if (!$name && !$username) {
            error_log('[es]: tried to save user without username or name ' . $this->entity->guid);
            return $map;
        }

        $inputs = [ $username, $name ];
        //split out the name based on CamelCase
        $nameParts = preg_split('/([\s])?(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $inputs = array_unique(array_merge($inputs, $this->permutateInputs($nameParts)));

        $map = array_merge($map, [
            'input' => array_values($inputs),
            'weight' => count(array_values($inputs)) == 1 ? 4 : 2
        ]);

        $map['weight'] += $this->entity->getSubscribersCount();

        if ($this->entity->isPro()) {
            $map['weight'] += 50;
        }

        if ($this->entity->featured_id) {
            $map['weight'] += 50;
        }

        if ($this->entity->isAdmin()) {
            $map['weight'] += 100;
        }

        if (strlen($username) > 30) {
            $map['weight'] = 1; //spammy username
        }

        if ($this->entity->icontime == $this->entity->time_created) {
            $map['weight'] = 0; //no avatar
        }

        return $map;
    }

    //

    /**
     * @param $inputs
     * @param int $calls
     * @return array
     */
    protected function permutateInputs($inputs, $calls = 0)
    {
        if (count($inputs) <= 1 || count($inputs) >= 4 || $calls > 5) {
            return $inputs;
        }

        $result = [];
        foreach ($inputs as $key => $item) {
            foreach ($this->permutateInputs(array_diff_key($inputs, [$key => $item]), $calls++) as $p) {
                $result[] = "$item $p";
            }
        }

        return $result;
    }
}
