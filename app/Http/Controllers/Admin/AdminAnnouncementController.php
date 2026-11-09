<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Announcement::query();
        if ($type = $request->query('kind')) {
            $query->where('kind', $type);
        }
        $announcements = $query->orderByDesc('starts_at')->paginate(20);
        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => 'required|in:info,warning,maintenance',
            'message' => 'required|string|max:300',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'required|boolean',
        ]);
        Announcement::create($validated);
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => 'required|in:info,warning,maintenance',
            'message' => 'required|string|max:300',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'required|boolean',
        ]);
        $announcement->update($validated);
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted');
    }
}
