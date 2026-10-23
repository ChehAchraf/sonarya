<?php

use App\Models\Patient;
use App\Models\User;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('admin can delete a patient record', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->delete("/patients/{$patient->id}")
        ->assertRedirect('/patients');

    expect(Patient::find($patient->id))->toBeNull()
        ->and(Patient::withTrashed()->find($patient->id))->not->toBeNull();
});

test('operator without delete patients permission cannot delete a patient record', function (): void {
    $operator = User::factory()->create();
    $operator->assignRole('operateur_saisie');

    $patient = Patient::factory()->create();

    $this->actingAs($operator)
        ->delete("/patients/{$patient->id}")
        ->assertForbidden();

    expect(Patient::find($patient->id))->not->toBeNull();
});
