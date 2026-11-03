<?php

declare(strict_types=1);

namespace App\Enums;

enum ChannelTrust: string
{
    case Official = 'official';
    case Trusted = 'trusted';
    case Blocked = 'blocked';
}
