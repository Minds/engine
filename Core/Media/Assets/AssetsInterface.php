<?php
namespace Minds\Core\Media\Assets;

use Minds\Entities\User;

interface AssetsInterface
{
    public function setEntity($entity);
    public function validate(array $media);
    public function upload(array $media, ?User $owner = null);
    public function update(array $data);
}
