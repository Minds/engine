<?php

/**
 * Mapping for activity documents
 *
 * @author emi
 */

namespace Minds\Core\Search\Mappings;

use Minds\Common\Access;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;

class ActivityMapping extends EntityMapping implements MappingInterface
{
    /**
     * ActivityMapping constructor.
     */
    public function __construct()
    {
        $this->mappings = array_merge($this->mappings, [
            'message' => [ 'type' => 'text', '$exportField' => 'message' ],
            'rating' => ['type' => 'integer', '$exportField' => 'rating'],
            'custom_type' => ['type' => 'text', '$exportField' => 'custom_type'],
            'entity_guid' => ['type' => 'text', '$exportField' => 'entity_guid'],
            'pending' =>  ['type' => 'boolean', '$exportField' => 'pending'],
            'license' => ['type' => 'text', '$exportField' => 'license'],
            'source'  => [ 'type' => 'text', '$exportField' => 'source' ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'activity';
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

        
        // AccessId hack. Public groups should be public.
        $container = $this->entity->getContainerEntity();
        if ($container instanceof Group && $this->entity->getAccessId() === $container->getGuid()) {
            $map['access_id'] = $container->isPublic() ? (string) Access::PUBLIC : $this->entity->getAccessId();
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

        $map['auto_caption'] = "";
        if (!empty($this->entity->getAutoCaption())) {
            $map['auto_caption'] = $this->entity->getAutoCaption();
        }

        $map['inferred_tags'] = [];
        if (!empty($this->entity->getInferredTags())) {
            $map['inferred_tags'] = $this->entity->getInferredTags();
        }

        if ($container instanceof User) {
            $map['plus'] = $container->isPlus();
        }

        // Some entities have bad data where a subtype has been set, do not map this
        unset($map['subtype']);

        return $map;
    }
}
