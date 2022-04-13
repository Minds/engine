<?php

namespace Minds\Core\Notifications\Push\System;

class Manager
{
    public function __construct(
        private ?Repository $repository = null
    ) {
        $this->repository ??= new Repository();
    }
}
