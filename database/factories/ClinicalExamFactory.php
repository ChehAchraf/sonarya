<?php

namespace Database\Factories;

use App\Models\ClinicalExam;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalExam>
 */
class ClinicalExamFactory extends Factory
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
            'exam_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'syndrome_pyramidal' => fake()->randomElement(['present', 'absent']),
            'syndrome_vestibulaire' => fake()->randomElement(['present', 'absent']),
            'syndrome_ataxo_spasmodique' => fake()->randomElement(['present', 'absent']),
            'syndrome_medullaire' => fake()->randomElement(['present', 'absent']),
            'norb' => fake()->randomElement(['present', 'absent']),
            'cranial_nerves_impairment' => fake()->randomElement(['present', 'absent']),
            'cranial_nerves_details' => null,
            'other_signs' => null,
            'edss_score' => fake()->randomElement(['0.0', '1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0', '6.0', '6.5']),
            'functional_systems_scores' => null,
            'evaluator_name' => 'Dr. Neurologue',
            'clinical_comment' => fake()->boolean(40) ? fake()->sentence() : null,
        ];
    }
}
