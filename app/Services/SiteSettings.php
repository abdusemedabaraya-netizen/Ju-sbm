<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    public const DEFAULTS = [
        'site_name'                => 'JIACRS',
        'contact_email'            => '',
        'contact_phone'            => '',
        'mail_notifications'       => '0',
        'public_reporting_enabled' => '1',
        'max_files'                => '5',
        'max_file_mb'              => '20',
    ];

    public static function all(): array
    {
        return Cache::rememberForever('jiacrs.settings', fn () => array_merge(
            self::DEFAULTS,
            Setting::pluck('value', 'key')->all()
        ));
    }

    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        }

        Cache::forget('jiacrs.settings');
    }

    /** Makes the saved settings visible to config('jiacrs.*') everywhere in the app. */
    public static function applyToConfig(): void
    {
        $s = self::all();

        config([
            'jiacrs.site_name'                => $s['site_name'],
            'jiacrs.contact_email'            => $s['contact_email'],
            'jiacrs.contact_phone'            => $s['contact_phone'],
            'jiacrs.mail_notifications'       => (bool) (int) $s['mail_notifications'],
            'jiacrs.public_reporting_enabled' => (bool) (int) $s['public_reporting_enabled'],
            'jiacrs.max_files'                => max(1, (int) $s['max_files']),
            'jiacrs.max_file_kb'              => max(1, (int) $s['max_file_mb']) * 1024,
        ]);
    }
}
