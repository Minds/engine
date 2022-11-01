<?php

namespace Minds\Core\Email\V2\SendLists;

class Factory
{
    /**
     * Build the campaign
     * @param  string $batch
     * @return SendListInterface
     */
    public static function build($sendList): SendListInterface
    {
        $sendList = ucfirst($sendList);
        $sendList = "Minds\\Core\\Email\\V2\\SendLists\\$sendList";
        if (class_exists($sendList)) {
            $class = new $sendList();
            if ($class instanceof SendListInterface) {
                return $class;
            }
        }
        throw new \Exception("SendList not found - $sendList");
    }
}
