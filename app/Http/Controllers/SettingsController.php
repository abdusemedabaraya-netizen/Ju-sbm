<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\SiteSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', ['s' => SiteSettings::all()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name'     => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'max_files'     => ['required', 'integer', 'min:1', 'max:10'],
            'max_file_mb'   => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        SiteSettings::save([
            'site_name'                => $data['site_name'],
            'contact_email'            => $data['contact_email'] ?? '',
            'contact_phone'            => $data['contact_phone'] ?? '',
            'max_files'                => $data['max_files'],
            'max_file_mb'              => $data['max_file_mb'],
            'mail_notifications'       => $request->boolean('mail_notifications') ? 1 : 0,
            'public_reporting_enabled' => $request->boolean('public_reporting_enabled') ? 1 : 0,
        ]);

        AuditLogger::log('settings.updated');

        return back()->with('success', 'Settings saved.');
    }
}
