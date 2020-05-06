<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Traits\MagicAttributes;

/**
 * @method string getItag()
 * @method string getUrl()
 */
class YTVideoSource
{
    use MagicAttributes;

    /** @var int */
    protected $itag;

    /** @var string */
    protected $url;

    /**
     * Copy from array
     * @param array $data
     * @return self
     */
    public function fromArray($data): self
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
        return $this;
    }
}
