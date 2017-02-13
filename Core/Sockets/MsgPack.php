<?php
/**
* Binary packing for Sockets
* Based on https://github.com/onlinecity/msgpack-php
*/
namespace Minds\Core\Sockets;

class MsgPack
{
    protected $bigendian;

    public function __construct($bigendian = null)
    {
    }

    public function pack($input)
    {
        return msgpack_pack($input);
    }
}
