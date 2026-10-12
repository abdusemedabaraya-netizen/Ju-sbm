<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['bribery',         'Bribery',                 'high'],
            ['fraud',           'Fraud / financial fraud', 'critical'],
            ['abuse_of_power',  'Abuse of power',          'high'],
            ['nepotism',        'Nepotism / favoritism',   'medium'],
            ['resource_misuse', 'Misuse of resources',     'medium'],
            ['harassment',      'Harassment',              'high'],
            ['other',           'Other',                   'low'],
        ];

        foreach ($rows as $i => [$slug, $name, $priority]) {
            // firstOrCreate: re-running never overwrites names an admin has edited
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'default_priority' => $priority, 'is_active' => true, 'sort_order' => $i]
            );
        }
    }
}
