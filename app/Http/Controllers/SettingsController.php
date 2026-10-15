<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Services\AuditLogger;
use App\Services\SiteSettings;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', ['s' => SiteSettings::all()]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();

        SiteSettings::save([
            'site_name'                => $data['site_name'],
            'contact_email'            => $data['contact_email'] ?? '',
            'contact_phone'            => $data['contact_phone'] ?? '',
            'max_files'                => $data['max_files'],
            'max_file_mb'              => $data['max_file_mb'],
            'mail_notifications'       => (int) $request->boolean('mail_notifications'),
            'public_reporting_enabled' => (int) $request->boolean('public_reporting_enabled'),
        ]);

        AuditLogger::log('settings.updated');

        return back()->with('success', 'Settings saved.');
    }
}
