<?php

/**
 * Mapping for video object documents
 *
 * @author emi
 */

namespace Minds\Core\Search\Mappings;

class ObjectVideoMapping extends EntityMapping implements MappingInterface
{
    /**
     * ObjectVideoMapping constructor.
     */
    public function __construct()
    {
        $this->mappings = array_merge($this->mappings, [
            'license' => ['type' => 'text', '$exportField' => 'license'],
            'rating' => ['type' => 'integer', '$exportField' => 'rating'],
            'youtube_id' => ['type' => 'text', '$exportField' => 'youtube_id'],
            'youtube_channel_id' => ['type' => 'text', '$exportField' => 'youtube_channel_id'],
            'transcoding_status' => ['type' => 'text', '$exportField' => 'transcoding_status'],
        ]);
    }

    /**
     * Map
     */
    public function map(array $defaultValues = [])
    {
        $map = parent::map($defaultValues);

        $map['is_portrait'] = $this->entity->height > $this->entity->width;

        return $map;
    }
}
