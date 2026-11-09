<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminFeedbackController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('type');

        $query = Feedback::query()->with('user');

        if ($status) {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $feedbacks = $query->latest()->paginate(20)->withQueryString();

        return view('admin.feedback.index', compact('feedbacks', 'status', 'type'));
    }

    public function updateStatus(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,read,in_progress,done,archived',
            'admin_note' => 'nullable|string|max:1000',
            'email_reply' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $feedback->status;

        $feedback->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? $feedback->admin_note,
            'handled_by' => $request->user('admin')?->id,
        ]);

        if (!empty($validated['email_reply']) && $feedback->user && $feedback->user->email) {
            Mail::raw($validated['email_reply'], function ($message) use ($feedback) {
                $message->to($feedback->user->email)
                    ->subject('Regarding your feedback to ' . config('app.name'));
            });
        }

        $this->auditLogger->log($request, 'feedback.status_updated', $feedback, [
            'old_status' => $oldStatus,
            'new_status' => $feedback->status,
            'admin_note' => $feedback->admin_note,
            'emailed' => !empty($validated['email_reply']),
        ]);

        return redirect()->back()->with('success', 'Feedback updated and status change audited.');
    }
}
