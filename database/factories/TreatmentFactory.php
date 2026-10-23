<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
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
            'category' => 'fond',
            'molecule_name' => 'Diméthylfumarate (Tecfidera)',
            'therapeutic_class' => 'Immunomodulateur oral',
            'start_date' => fake()->dateTimeBetween('-2 years', '-6 months')->format('Y-m-d'),
            'end_date' => null,
            'status' => 'en_cours',
            'dosage_regimen' => '240 mg x 2/jour',
            'administration_route' => 'Orale (PO)',
            'prescription_reason' => 'Forme récurrente-rémittente active en première ligne',
            'stop_reason' => null,
            'adverse_effects' => 'Flushs vasomoteurs discrets en début de traitement',
            'relapses_after_two_years' => 0,
            'vitamin_d_supplementation' => true,
            'antidepressants' => false,
            'notes' => 'Bonne observance thérapeutique rapportée par le patient.',
        ];
    }
}
