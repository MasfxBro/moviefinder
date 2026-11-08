<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['admin_id', 'action', 'subject_type', 'subject_id', 'ip_hash', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
