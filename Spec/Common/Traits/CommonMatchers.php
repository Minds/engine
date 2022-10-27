<?php

namespace Spec\Minds\Common\Traits;

trait CommonMatchers
{
    public function getMatchers(): array
    {
        return  [
            'containValueLike' => function ($subject, $value) {
                foreach ($subject as $item) {
                    if ($item == $value) {
                        return true;
                    }
                }
                return false;
            }
        ];
    }
}
