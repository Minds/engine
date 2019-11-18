<?php

namespace Minds\Helpers;

class File
{
    const HEADER_LENGTH = 16;

    public static function getMimeType(&$data): string
    {
        return self::getType($data, FILEINFO_MIME_TYPE);
    }

    public static function getMime(&$data): string
    {
        return self::getType($data, FILEINFO_MIME);
    }

    protected static function getType(&$data, int $type): string
    {
        $header = substr($data, 0, self::HEADER_LENGTH);
        $finfo = new \finfo($type);
        $type = $finfo->buffer($header);
        unset($finfo);
        return $type;
    }
}
