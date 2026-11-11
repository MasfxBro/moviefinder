<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->query('q');
        $logs = AuditLog::when($q, fn ($query) => $query->where('action', 'like', "%{$q}%")
            ->orWhere('ip_hash', 'like', "%{$q}%"))
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
