<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@sonarya.med'],
            [
                'name' => 'Dr. Administrateur',
                'password' => 'Admin@123456!',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $medecin = User::firstOrCreate(
            ['email' => 'medecin@sonarya.med'],
            [
                'name' => 'Dr. Neurologue',
                'password' => 'Doctor@123456!',
                'email_verified_at' => now(),
            ]
        );
        $medecin->assignRole('medecin');

        // Create sample patients only in non-production environments
        if (! app()->isProduction()) {
            Patient::factory()->count(15)->create([
                'created_by' => $medecin->id,
                'updated_by' => $medecin->id,
            ]);
        }
    }
}
