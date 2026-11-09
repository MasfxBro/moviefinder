<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $q = $request->query('q');
        $logs = AuditLog::when($q, fn ($query) => $query->where('action', 'like', "%{$q}%")
            ->orWhere('ip_hash', 'like', "%{$q}%"))
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
