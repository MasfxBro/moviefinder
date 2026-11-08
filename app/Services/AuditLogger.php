<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(Request $request, string $action, ?object $subject = null, array $metadata = []): AuditLog
    {
        $admin = $request->user('admin') ?? $request->user();

        return AuditLog::create([
            'admin_id' => $admin?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip_hash' => hash_hmac('sha256', (string) ($request->ip() ?? '127.0.0.1'), (string) config('app.key')),
            'metadata' => $metadata,
        ]);
    }
}
