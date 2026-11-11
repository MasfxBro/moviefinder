<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    use AuthorizesRequests;

    /** Search users */
    public function index(Request $request)
    {
        $q = $request->query('q');
        $users = User::when($q, fn ($qb) => $qb->where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%"))
            ->paginate(20);

        return response()->json($users);
    }

    /** Summary of a user */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'plan' => $user->plan()->value,
            'created_at' => $user->created_at,
        ]);
    }

    /** Grant plan manually */
    public function grantPlan(Request $request, User $user)
    {
        $data = $request->validate([
            'plan' => 'required|string',
            'reason' => 'required|string',
            'expires_at' => 'required|date',
        ]);
        // Assume Plan enum exists and has from method
        $plan = Plan::from($data['plan']);
        $user->update(['plan' => $plan]);
        AdminAction::create([
            'admin_user_id' => $request->user('admin')->id,
            'target_user_id' => $user->id,
            'action' => 'grant_plan',
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'],
        ]);

        return response()->json(['status' => 'plan granted']);
    }

    /** Revoke plan */
    public function revokePlan(Request $request, User $user)
    {
        $data = $request->validate([
            'reason' => 'required|string',
        ]);
        $user->update(['plan' => Plan::Free]);
        AdminAction::create([
            'admin_user_id' => $request->user('admin')->id,
            'target_user_id' => $user->id,
            'action' => 'revoke_plan',
            'reason' => $data['reason'],
            'expires_at' => null,
        ]);

        return response()->json(['status' => 'plan revoked']);
    }

    /** Suspend user */
    public function suspend(Request $request, User $user)
    {
        $data = $request->validate([
            'reason' => 'required|string',
        ]);
        $user->update(['suspended_at' => now()]);
        AdminAction::create([
            'admin_user_id' => $request->user('admin')->id,
            'target_user_id' => $user->id,
            'action' => 'suspend',
            'reason' => $data['reason'],
            'expires_at' => null,
        ]);

        return response()->json(['status' => 'user suspended']);
    }

    /** Force password reset */
    public function resetPassword(Request $request, User $user)
    {
        $newPassword = 'Temp1234!';
        $user->update(['password' => Hash::make($newPassword)]);
        AdminAction::create([
            'admin_user_id' => $request->user('admin')->id,
            'target_user_id' => $user->id,
            'action' => 'force_password_reset',
            'reason' => 'admin reset',
            'expires_at' => null,
        ]);

        return response()->json(['status' => 'password reset', 'temporary_password' => $newPassword]);
    }

    /** Reset guided tour (calls T‑30B) */
    public function resetTour(Request $request, User $user)
    {
        // Reuse existing onboarding reset command (artisan) via service
        app(OnboardingService::class)->reset($user);
        AdminAction::create([
            'admin_user_id' => $request->user('admin')->id,
            'target_user_id' => $user->id,
            'action' => 'reset_tour',
            'reason' => 'admin reset',
            'expires_at' => null,
        ]);

        return response()->json(['status' => 'tour reset']);
    }

    /** Delete user */
    public function destroy(Request $request, User $user)
    {
        $user->delete();
        AdminAction::create([
            'admin_user_id' => $request->user('admin')->id,
            'target_user_id' => $user->id,
            'action' => 'delete_user',
            'reason' => 'admin delete',
            'expires_at' => null,
        ]);

        return response()->noContent();
    }

    public function watchlist(Request $request, User $user)
    {
        $this->authorize('viewWatchlist', $user);

        return response()->json([]);
    }

    public function history(Request $request, User $user)
    {
        $this->authorize('viewHistory', $user);

        return response()->json([]);
    }
}
