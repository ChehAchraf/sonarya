<?php

use App\Models\Patient;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and see clinical metrics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Patient::factory()->count(3)->create();

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('kpis.total_patients')
            ->has('kpis.sex_ratio')
            ->has('clinical_forms')
            ->has('top_treatments')
            ->has('edss_distribution')
            ->has('recent_patients')
        );
});
