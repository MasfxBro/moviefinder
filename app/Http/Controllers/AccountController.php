<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PlanResolver;
use App\Models\User;
use App\Services\Ai\AiAccessState;
use App\Services\Ai\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request, PlanResolver $resolver, PlanCatalog $catalog, AiAccessState $access): View
    {
        $user = $request->user();
        $plan = $resolver->resolve($user);
        $quota = $access->for($user);

        return view('account.show', [
            'user' => $user,
            'plan' => $plan,
            'limits' => $catalog->get($plan),
            'quota' => $quota,
            'subscription' => $user->subscriptions()->whereIn('status', ['active', 'past_due', 'canceled'])->latest()->first(),
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $request->user()->update($validated);

        return back()->with('status', 'name-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            if (DB::getSchemaBuilder()->hasTable('payments') && DB::getSchemaBuilder()->hasColumn('payments', 'user_id')) {
                DB::table('payments')->where('user_id', $user->id)->update(['user_id' => null]);
            }

            DB::table('users')->where('id', $user->id)->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
