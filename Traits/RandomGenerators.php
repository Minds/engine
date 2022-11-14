<?php
declare(strict_types=1);

namespace Minds\Traits;

trait RandomGenerators
{
    public function generateRandomInteger($len = 6)
    {
        $last =-1;
        $code = '';
        for ($i=0;$i<$len;$i++) {
            do {
                $next_digit=mt_rand(0, 9);
            } while ($next_digit == $last);
            $last=$next_digit;
            $code.=$next_digit;
        }
        return $code;
    }
}
