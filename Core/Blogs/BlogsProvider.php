<?php

namespace Minds\Core\Blogs;

use Minds\Core\Di\Provider;

class BlogsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Blogs\Manager', function () {
            return new Manager();
        });
    }
}
