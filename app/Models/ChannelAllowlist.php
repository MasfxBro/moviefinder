<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelAllowlist extends Model
{
    protected $table = 'channel_allowlist';

    protected $fillable = ['platform', 'channel_id', 'channel_name', 'trust', 'notes'];
}
