<?php

namespace Minds\Core\MultiTenant\Enums;

enum DnsRecordEnum: string
{
    case TXT = 'txt';
    case A = 'a';
    case CNAME = 'cname';

}
