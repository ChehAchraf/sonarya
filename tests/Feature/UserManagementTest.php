<?php

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('admin can access user management page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users')
            ->has('metrics')
            ->has('availableRoles')
        );
});

test('doctor without manage users permission cannot access user management page', function (): void {
    $doctor = User::factory()->create();
    $doctor->assignRole('medecin');

    $this->actingAs($doctor)
        ->get('/users')
        ->assertForbidden();
});

test('admin can create a new medical staff user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/users', [
            'name' => 'Dr. Souad Berrada',
            'email' => 's.berrada@sonarya.med',
            'role' => 'medecin',
            'password' => 'Password@123456!',
        ])
        ->assertRedirect('/users');

    $newUser = User::where('email', 's.berrada@sonarya.med')->firstOrFail();
    expect($newUser->hasRole('medecin'))->toBeTrue()
        ->and($newUser->name)->toBe('Dr. Souad Berrada');
});

test('admin can update user role', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Resident Test']);
    $target->assignRole('operateur_saisie');

    $this->actingAs($admin)
        ->put("/users/{$target->id}", [
            'name' => 'Dr. Resident Test',
            'email' => $target->email,
            'role' => 'medecin',
        ])
        ->assertRedirect('/users');

    $target->refresh();
    expect($target->hasRole('medecin'))->toBeTrue()
        ->and($target->hasRole('operateur_saisie'))->toBeFalse();
});

test('admin cannot delete own account', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->delete("/users/{$admin->id}")
        ->assertRedirect('/users')
        ->assertSessionHas('error');

    expect(User::find($admin->id))->not->toBeNull();
});

test('admin can delete another user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('operateur_saisie');

    $this->actingAs($admin)
        ->delete("/users/{$target->id}")
        ->assertRedirect('/users');

    expect(User::find($target->id))->toBeNull();
});
