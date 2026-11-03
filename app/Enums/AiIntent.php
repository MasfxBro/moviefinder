<?php

declare(strict_types=1);

namespace App\Enums;

enum AiIntent: string
{
    case FindTitle = 'find_title';
    case FindScene = 'find_scene';
    case Answer = 'answer';
}
