<?php
namespace Minds\Core\Media\Video;

use Minds\Traits\MagicAttributes;

/**
 * @method string getGuid()
 * @method Source setGuid(string $guid)
 * @method string getSrc()
 * @method Source setSrc(string $src)
 * @method string getType()
 * @method Source setType(string $type)
 * @method int getSize()
 * @method Source setSize(int $size)
 * @method string getLabel()
 * @method Source setLabel(string $label)
 */
class Source
{
    use MagicAttributes;

    /** @var string */
    protected $guid;

    /** @var string */
    protected $src;

    /** @var string */
    protected $type;

    /** @var int */
    protected $size;

    /** @var string */
    protected $label;

    /**
     * Export source
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'guid' => $this->guid,
            'src' => $this->src,
            'type' => $this->type,
            'size' => $this->size,
            'label' => $this->label,
        ];
    }
}
