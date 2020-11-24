<?php
namespace Minds\Core\Discovery;

use Minds\Traits\MagicAttributes;

class Trend
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $hashtag;

    /** @var string */
    protected $title;

    /** @var Entity */
    protected $entity;

    /** @var string */
    protected $guid;

    /** @var int */
    protected $volume;

    /** @var int */
    protected $period;

    /** @var bool */
    protected $deleted = false;

    /** @var bool */
    protected $selected;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = [])
    {
        return [
            'id' => $this->id,
            'entity' => $this->entity ? $this->entity->export() : null,
            'guid' => $this->guid,
            'hashtag' => $this->hashtag,
            'title' => $this->title,
            'volume' => $this->volume,
            'period' => $this->period,
            'selected' => $this->selected,
        ];
    }
}
