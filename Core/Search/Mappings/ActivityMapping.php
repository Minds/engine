<?php

/**
 * Mapping for activity documents
 *
 * @author emi
 */

namespace Minds\Core\Search\Mappings;

use Minds\Entities\Activity;

class ActivityMapping extends EntityMapping implements MappingInterface
{
    /**
     * ActivityMapping constructor.
     */
    public function __construct()
    {
        $this->mappings = array_merge($this->mappings, [
            'rating' => ['type' => 'integer', '$exportField' => 'rating'],
            'custom_type' => ['type' => 'text', '$exportField' => 'custom_type'],
            'entity_guid' => ['type' => 'text', '$exportField' => 'entity_guid'],
            'pending' =>  ['type' => 'boolean', '$exportField' => 'pending'],
            'license' => ['type' => 'text', '$exportField' => 'license'],
        ]);
    }

    /**
     * Map
     */
    public function map(array $defaultValues = [])
    {
        $map = parent::map($defaultValues);

        if (!$this->entity instanceof Activity) {
            return $map;
        }


        //
        $map['is_portrait'] = $this->entity->isPortrait();

        if ($this->entity->hasAttachments()) {
            $map['custom_type'] = $this->entity->getCustomType();
        }

        // Reminds

        $map['is_remind'] = $this->entity->isRemind();
        $map['is_quoted_post'] = $this->entity->isQuotedPost();

        if ($map['is_remind'] || $map['is_quoted_post']) {
            $map['remind_guid'] = (string) $this->entity->get('remind_object')['guid'];
        }

        $map['is_supermind'] = false;
        if (!empty($this->entity->supermind)) {
            $map['is_supermind'] = true;
            $map['supermind_request_guid'] = $this->entity->supermind['request_guid'];
            $map['is_supermind_reply'] = $this->entity->supermind['is_reply'];
        }


        return $map;
    }
}
