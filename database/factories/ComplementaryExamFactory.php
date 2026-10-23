<?php

namespace Database\Factories;

use App\Models\ComplementaryExam;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplementaryExam>
 */
class ComplementaryExamFactory extends Factory
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
            'exam_type' => 'Ponction Lombaire & Biologie',
            'pl_result' => 'pathologique',
            'pl_cytorachie' => 3.0,
            'pl_proteinorachie' => 0.42,
            'pl_oligoclonal_bands' => 'positives',
            'biology_anti_nmo' => 'negative',
            'biology_anti_mog' => 'negative',
            'biology_anti_antinucleaire' => 'negative',
            'biology_anti_bgsa' => 'negative',
            'biology_serology_hbv' => 'negative',
            'biology_other' => 'Sérologies syphilis et VIH négatives',
            'iepp_sample_type' => 'sang_et_lcr',
            'iepp_result' => 'Profil de type 2 : Synthèse intrathécale exclusive d\'IgG avec présence de bandes oligoclonales dans le LCR absentes dans le sérum.',
            'report_file_path' => null,
            'notes' => 'Profil biologique et LCR typique de sclérose en plaques.',
        ];
    }
}
