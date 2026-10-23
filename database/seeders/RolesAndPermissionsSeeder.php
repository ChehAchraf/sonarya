<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            'manage users',
            'view activity logs',
            'manage settings',
            'view patients',
            'create patients',
            'edit patients',
            'delete patients',
            'manage clinical data',
            'manage imaging',
            'manage treatments',
            'manage consultations',
            'export research data',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 1. Role Administrateur
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        // 2. Role Médecin / Consultant
        $medecinRole = Role::findOrCreate('medecin', 'web');
        $medecinRole->syncPermissions([
            'view patients',
            'create patients',
            'edit patients',
            'manage clinical data',
            'manage imaging',
            'manage treatments',
            'manage consultations',
            'export research data',
        ]);

        // 3. Role Opérateur de saisie / Collaborateur de recherche
        $saisieRole = Role::findOrCreate('operateur_saisie', 'web');
        $saisieRole->syncPermissions([
            'view patients',
            'create patients',
            'edit patients',
            'manage clinical data',
            'manage treatments',
            'manage consultations',
        ]);
    }
}
