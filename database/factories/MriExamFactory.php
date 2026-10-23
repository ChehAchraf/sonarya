<?php

namespace Database\Factories;

use App\Models\MriExam;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MriExam>
 */
class MriExamFactory extends Factory
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
            'timing' => fake()->randomElement(['baseline', 'rebaseline_6m', 'control']),
            'region' => fake()->randomElement(['cerebrale', 'medullaire', 'les_deux']),
            'sep_protocol_applied' => true,
            'sequences_available' => 'T1 3D, T2 FLAIR 3D, T2 TSE, Diffusion, T1 3D + Gado',
            'lesions_count_estimated' => fake()->randomElement(['0', '1-3', '4-9', '≥ 10']),
            'lesions_locations' => 'Périventriculaires et juxtacorticales',
            'contrast_enhancement' => fake()->randomElement(['negative', 'positive', 'non_evaluee']),
            'new_lesions_count' => fake()->numberBetween(0, 2),
            'radiologist_report' => 'Examen IRM compatible avec le protocole SEP McDonald.',
            'notes' => null,
            'key_images' => null,
        ];
    }
}
