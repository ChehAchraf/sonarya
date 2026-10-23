<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
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
            'consultation_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'doctor_id' => null,
            'doctor_name' => 'Dr. '.fake()->lastName(),
            'intercurrent_events' => 'Aucun événement intercurrent notable. Pas d\'épisode fébrile récent.',
            'relapses_since_last_visit' => 0,
            'edss_score' => 2.5,
            'current_treatment_evaluation' => 'Traitement bien toléré, pas d\'effets indésirables rapportés.',
            'tolerance_and_adherence' => 'Excellente observance thérapeutique, prise régulière sans oubli.',
            'recent_exam_results' => 'IRM cérébrale de contrôle stable sans nouvelle lésion en T2 ni prise de contraste.',
            'therapeutic_decision' => 'Maintien du traitement de fond actuel à la même posologie.',
            'next_appointment_date' => fake()->dateTimeBetween('+3 months', '+6 months')->format('Y-m-d'),
            'notes' => 'Patient autonome dans les activités de la vie quotidienne.',
        ];
    }
}
