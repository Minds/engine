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

        $isPortrait = false;

        if ($this->entity->custom_type === 'video' && is_array($this->entity->custom_data)) {
            $isPortrait = $this->entity->custom_data['height'] > $this->entity->custom_data['width'];
        }

        if (
            in_array($this->entity->custom_type, ['image', 'batch'], true) &&
            is_array($this->entity->custom_data) &&
            is_array($this->entity->custom_data[0])
        ) {
            $isPortrait = $this->entity->custom_data[0]['height'] > $this->entity->custom_data[0]['width'];
        }

        $map['is_portrait'] = $isPortrait;

        // Reminds

        $map['is_remind'] = $this->entity->isRemind();
        $map['is_quoted_post'] = $this->entity->isQuotedPost();

        if ($map['is_remind'] || $map['is_quoted_post']) {
            $map['remind_guid'] = (string) $this->entity->get('remind_object')['guid'];
        }

        return $map;
    }
}
