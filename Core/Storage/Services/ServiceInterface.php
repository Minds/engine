<?php

namespace Minds\Core\Storage\Services;

interface ServiceInterface
{
    public function open($path, $mode): self;

    public function close();

    public function write($data);

    public function read($length);

    public function stats(): array;

    public function destroy();
}
