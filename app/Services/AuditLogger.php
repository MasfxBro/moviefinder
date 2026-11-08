<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(Request $request, string $action, ?object $subject = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'admin_id' => $request->user('admin')->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'metadata' => $metadata,
        ]);
    }
}
