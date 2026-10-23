<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Relapse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Relapse>
 */
class RelapseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'start_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'symptoms' => fake()->randomElement([
                'Névrite optique rétrobulbaire gauche avec baisse de l\'acuité visuelle',
                'Paresthésies et engourdissements des deux membres inférieurs',
                'Déficit moteur / paraparésie spasmodique d\'aggravation rapide',
                'Diplopie horizontale et ophtalmoplégie internucléaire',
                'Syndrome cérébelleux aigu avec instabilité majeure à la marche',
            ]),
            'is_before_diagnosis' => fake()->boolean(30),
            'treatment_administered' => fake()->randomElement([
                'Bolus Solumédrol 1g/j IV x 3 jours',
                'Bolus Solumédrol 1g/j IV x 5 jours',
                'Corticothérapie orale forte dose',
                'Aucun',
            ]),
            'administration_route' => 'Intraveineuse (IV)',
            'clinical_recovery' => fake()->randomElement(['Complète', 'Incomplète', 'Nulle']),
            'sequelae' => null,
            'hospitalization_required' => fake()->boolean(25),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }
}
