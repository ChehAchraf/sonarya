<?php

use App\Models\Patient;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('admin can access audit trail page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/audit-trail')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AuditTrail/Index')
            ->has('activities')
            ->has('metrics')
        );
});

test('unauthenticated user cannot access audit trail page', function (): void {
    $this->get('/audit-trail')
        ->assertRedirect('/login');
});

test('operator without view activity logs permission cannot access audit trail', function (): void {
    $operator = User::factory()->create();
    $operator->assignRole('operateur_saisie');

    $this->actingAs($operator)
        ->get('/audit-trail')
        ->assertForbidden();
});

test('creating and updating a patient generates activity log entries', function (): void {
    $doctor = User::factory()->create();
    $doctor->assignRole('medecin');

    $this->actingAs($doctor)
        ->post('/patients', [
            'dossier_number' => 'SEP-2026-9999',
            'first_name' => 'Fatima',
            'last_name' => 'Zahra',
            'gender' => 'female',
            'birth_date' => '1995-04-12',
            'status' => 'active',
            'city' => 'Tanger',
        ])
        ->assertRedirect('/patients');

    $patient = Patient::where('dossier_number', 'SEP-2026-9999')->firstOrFail();

    expect(Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)->count())
        ->toBeGreaterThan(0);
});

test('exporting research cohort creates an export audit log entry', function (): void {
    $doctor = User::factory()->create();
    $doctor->assignRole('medecin');

    $initialCount = Activity::where('log_name', 'export')->count();

    $this->actingAs($doctor)
        ->get('/research/export?pseudonymize=1')
        ->assertOk();

    expect(Activity::where('log_name', 'export')->count())
        ->toBeGreaterThan($initialCount);
});
