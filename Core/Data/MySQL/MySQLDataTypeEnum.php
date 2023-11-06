<?php
namespace Minds\Core\Data\MySQL;

enum MySQLDataTypeEnum
{
    case INT;
    case BIGINT;
    case BLOB;
    case TEXT;
    case JSON;
    case BOOL;
    case TIMESTAMP;
}
