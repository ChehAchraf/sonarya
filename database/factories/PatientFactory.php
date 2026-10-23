<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PatientStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male, Gender::Female]);

        return [
            'dossier_number' => 'SEP-'.fake()->unique()->numerify('2026-####'),
            'cin' => strtoupper(fake()->bothify('??######')),
            'first_name' => $gender === Gender::Male ? fake()->firstNameMale() : fake()->firstNameFemale(),
            'last_name' => strtoupper(fake()->lastName()),
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->randomElement(['Tanger', 'Tétouan', 'Larache', 'Al Hoceïma', 'Chefchaouen', 'Asilah', 'Ouezzane']),
            'origin' => fake()->randomElement(['Tanger-Assilah', 'Tétouan', 'Rif / Al Hoceïma', 'Larache', 'Gharb', 'Autre']),
            'address' => fake()->streetAddress(),
            'profession' => fake()->jobTitle(),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'children_count' => fake()->numberBetween(0, 4),
            'education_level' => fake()->randomElement(['Primaire', 'Collégial', 'Secondaire', 'Universitaire', 'Analphabète']),
            'health_coverage' => fake()->randomElements(['CNSS', 'AMO', 'Sans couverture', 'Autre'], fake()->numberBetween(1, 2)),
            'tobacco_consumption' => fake()->randomElement(['Non-fumeur', 'Fumeur actif (10 PA)', 'Sevré depuis 2 ans', null]),
            'alcohol_consumption' => fake()->randomElement(['Non consommateur', 'Occasionnel', null]),
            'pregnancies_count' => $gender === Gender::Female ? fake()->numberBetween(0, 3) : null,
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'referring_doctor' => 'Dr. '.fake()->lastName(),
            'status' => PatientStatus::Active,
            'notes' => fake()->boolean(40) ? fake()->sentence() : null,
        ];
    }
}
