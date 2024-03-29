<?php
namespace Minds\Core\Media\Assets;

use Minds\Core;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;

class Video implements AssetsInterface
{
    /** @var EntityInterface */
    protected $entity;

    /** @var bool */
    protected $doSave = true;

    public function __construct(
        private ?Save $save = null,
    ) {
        $this->save ??= new Save();
    }

    public function setEntity($entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Disables to save action
     * @param bool $doSave
     * @return self
     */
    public function setDoSave(bool $doSave): self
    {
        $this->doSave = $doSave;
        return $this;
    }

    public function validate(array $media)
    {
        $maxMins = 40;

        if ($media['length']) {
            // length is already in minutes
            $length = $media['length'];
        } else {
            $length = exec("ffmpeg -i {$media['file']} 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");
            $timeSplit = explode(':', $length);

            $hours = $timeSplit[0];
            $mins = $timeSplit[1];

            $length = ((int) $hours * 60) + (int) $mins;
        }

        if ($length >= $maxMins) {
            throw new \Exception("Sorry, the video is too long ({$length}m). It should be shorter than {$maxMins}m.");
        }

        return true;
    }

    public function upload(array $media, ?User $owner = null)
    {
        return [
            'media' => $media
        ];
    }

    public function update(array $data = [])
    {
        $assets = [];

        if (isset($data['file'])) {
            $img = preg_replace('#^data:image/[^;]+;base64,#', '', $data['file']);
            $img = str_replace(' ', '+', $img);

            $imagick = new \Imagick();
            $imagick->readImageBlob(base64_decode($img, true));
            $imagick->setImageFormat('jpeg');
            $data = $imagick->getImageBlob();

            $filename = "archive/thumbnails/{$this->entity->guid}.jpg";

            $file = new Entities\File();
            $file->owner_guid = $this->entity->owner_guid;
            $file->setFilename($filename);
            $file->open('write');
            $file->write($data);
            $file->close();

            if ($this->doSave) {
                $this->entity->thumbnail = $filename;
                $this->entity->last_updated = time();
                $this->save
                    ->setEntity($this->entity)
                    ->withMutatedAttributes([
                        'thumbnail',
                        'last_updated',
                    ])
                    ->save();
            }

            $assets['thumbnail'] = $filename;
        }

        return $assets;
    }
}
