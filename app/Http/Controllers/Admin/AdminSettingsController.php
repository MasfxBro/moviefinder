<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    /**
     * Show all settings.
     */
    public function index(Settings $settings): View
    {
        $all = DB::table('settings')->orderBy('key')->get();

        return view('admin.settings.index', ['settings' => $all]);
    }

    /**
     * Update a setting.
     */
    public function update(Request $request, Settings $settings, string $key): RedirectResponse
    {
        $allowed = ['plan.', 'ai.', 'billing.', 'features.', 'site.'];
        $valid = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($key, $prefix)) {
                $valid = true;
                break;
            }
        }
        if (! $valid) {
            return redirect()->back()->with('error', 'Invalid setting key.');
        }

        $rules = ['value' => 'required'];
        if (in_array($key, ['site.contact_email', 'site.copyright_email'])) {
            $rules['value'] = 'required|email';
        }
        $validated = $request->validate($rules);
        $old = DB::table('settings')->where('key', $key)->value('value');
        $value = json_decode($validated['value'], true);
        $value = $value === null ? $validated['value'] : $value;
        $settings->set($key, $value, $request->user('admin')?->id);
        // audit
        app(AuditLogger::class)->log($request, 'settings.update', null, ['key' => $key, 'old' => $old, 'new' => $validated['value']]);

        return redirect()->back()->with('success', "Setting {$key} updated.");
    }
}
