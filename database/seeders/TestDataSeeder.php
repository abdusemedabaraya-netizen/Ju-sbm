<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Super Admin', 'Admin', 'Investigator', 'User'] as $role) {
            Role::findOrCreate($role);
        }

        $accounts = [
            ['superadmin@test.com', 'Super Admin'],
            ['admin@test.com', 'Admin'],
            ['investigator@test.com', 'Investigator'],
            ['user@test.com', 'User'],
        ];
        foreach ($accounts as [$email, $role]) {
            $u = User::firstOrCreate(
                ['email' => $email],
                ['name' => $role . ' Test', 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $u->assignRole($role);
        }

        // Departments
        $deptIds = [];
        foreach (['Registrar', 'Finance', 'Human Resources', 'Procurement', 'College of Engineering'] as $name) {
            $dept = DB::table('departments')->where('name', $name)->first();
            $deptIds[] = $dept->id ?? DB::table('departments')->insertGetId([
                'name' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $categories = ['bribery', 'fraud', 'abuse_of_power', 'nepotism', 'resource_misuse', 'harassment', 'other'];
        $statuses   = ['submitted', 'under_review', 'assigned', 'investigating', 'resolved', 'closed', 'rejected'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        for ($i = 1; $i <= 30; $i++) {
            $tracking = sprintf('JU-2026-%06d', $i);
            $payload = [
                'department_id'   => $deptIds[array_rand($deptIds)],
                'category'        => $categories[array_rand($categories)],
                'subject'         => 'Sample report number ' . $i,
                'description'     => 'Sample description for testing the dashboard.',
                'priority'        => $priorities[array_rand($priorities)],
                'status'          => $statuses[array_rand($statuses)],
                'anonymous'       => (bool) rand(0, 1),
                'created_at'      => now()->subDays(rand(0, 150)),
                'updated_at'      => now(),
            ];
            if (DB::table('reports')->where('tracking_number', $tracking)->exists()) {
                DB::table('reports')->where('tracking_number', $tracking)->update($payload);
            } else {
                DB::table('reports')->insert($payload + ['tracking_number' => $tracking]);
            }
        }
    }
}