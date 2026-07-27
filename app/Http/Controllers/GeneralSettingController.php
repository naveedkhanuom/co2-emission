<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * General (global, app-wide) settings — currently the app name and the logo
 * shown on the login screen and as the sidebar fallback. Restricted to
 * super-admins because it affects every tenant.
 */
class GeneralSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            abort_unless($user && ($user->is_super_admin || $user->hasRole('Super Admin')), 403, 'Only a super administrator can change general settings.');

            return $next($request);
        });
    }

    public function edit()
    {
        return view('settings.general', [
            'appName' => Setting::get('app_name', config('app.name')),
            'appLogo' => Setting::get('app_logo'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name'    => 'nullable|string|max:255',
            'app_logo'    => 'nullable|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        if ($request->filled('app_name')) {
            Setting::set('app_name', $request->input('app_name'));
        }

        $current = Setting::get('app_logo');

        // Remove the existing logo when requested.
        if ($request->boolean('remove_logo') && $current) {
            if (!Str::startsWith($current, ['http://', 'https://'])) {
                Storage::disk('public')->delete($current);
            }
            Setting::set('app_logo', null);
        }

        // Replace with a newly uploaded logo (deletes the old file).
        if ($request->hasFile('app_logo')) {
            if ($current && !Str::startsWith($current, ['http://', 'https://'])) {
                Storage::disk('public')->delete($current);
            }
            Setting::set('app_logo', $request->file('app_logo')->store('app', 'public'));
        }

        return back()->with('success', 'General settings saved successfully.');
    }
}
