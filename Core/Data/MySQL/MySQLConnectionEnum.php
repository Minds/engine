<?php
namespace Minds\Core\Data\MySQL;

enum MySQLConnectionEnum: string
{
    case MASTER = 'master';
    case REPLICA = 'replica';
    case READ_ONLY = 'rdonly';
}
