<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Diagnostic;
use App\Models\Message;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Report;
use App\Models\UnavailabilityPeriod;
use App\Models\User;
use App\Services\CpfEncryptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $cpfService = app(CpfEncryptionService::class);

        // ─── USERS ───────────────────────────────────────────────
        // Admin
        $adminUser = User::factory()->create([
            'name' => 'Admin Principal',
            'email' => 'admin@larahealth.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        // Operational
        $opUser = User::factory()->create([
            'name' => 'Operador João',
            'email' => 'operacional@larahealth.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Operational,
        ]);

        // 10 patient users
        $patientUsers = User::factory(10)->create([
            'role' => UserRole::Patient,
        ]);

        // ─── PROFESSIONALS ───────────────────────────────────────
        $specialties = [
            'Cardiologia', 'Dermatologia', 'Ortopedia', 'Pediatria',
            'Neurologia', 'Ginecologia', 'Oftalmologia', 'Psiquiatria',
            'Endocrinologia', 'Urologia',
        ];

        $professionals = [];
        foreach ($specialties as $i => $specialty) {
            $user = User::factory()->create([
                'name' => "Dr(a). {$specialty}",
                'email' => "dr.{$specialty}@larahealth.com",
                'password' => Hash::make('password'),
                'role' => UserRole::Operational,
            ]);

            $professionals[] = Professional::factory()->create([
                'user_id' => $user->id,
                'full_name' => "Dr(a). {$specialty} Silva",
                'specialty' => $specialty,
                'professional_document' => fake()->numerify('CRM/'.str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT).' #####'),
                'phone' => fake()->phoneNumber(),
                'is_active' => true,
            ]);
        }

        // ─── PATIENTS ────────────────────────────────────────────
        $patients = [];
        foreach ($patientUsers as $i => $user) {
            $cpfData = $cpfService->encrypt(fake()->numerify('###########'));
            $patient = Patient::create([
                'user_id' => $user->id,
                'full_name' => $user->name,
                'date_of_birth' => fake()->date('Y-m-d', '2000-12-31'),
                'cpf_encrypted' => $cpfData['encrypted'],
                'cpf_hash' => $cpfData['hash'],
                'main_complaint' => fake()->optional(0.5)->sentence(6),
                'street' => fake()->streetAddress(),
                'neighborhood' => fake()->optional(0.7)->word(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'zip_code' => fake()->postcode(),
                'clinical_history' => fake()->optional(0.6)->sentence(10),
            ]);
            $patients[] = $patient;
        }

        // ─── APPOINTMENTS ────────────────────────────────────────
        $paymentMethods = ['credit_card', 'debit_card', 'cash', 'health_insurance', 'pix'];
        $appointments = [];

        foreach ($patients as $patient) {
            // 1-3 appointments per patient
            $numAppts = rand(1, 3);
            for ($j = 0; $j < $numAppts; $j++) {
                $apptDate = fake()->dateTimeBetween('-3 months', '+1 month');
                $appointments[] = Appointment::create([
                    'patient_id' => $patient->id,
                    'professional_id' => $professionals[array_rand($professionals)]->id,
                    'appointment_date' => $apptDate->format('Y-m-d H:i:s'),
                    'is_paid' => fake()->boolean(60),
                    'payment_method' => fake()->optional(0.7)->randomElement($paymentMethods),
                    'paid_at' => fake()->optional(0.4)->dateTimeBetween('-2 months', 'now'),
                    'is_return' => fake()->boolean(30),
                    'notes' => fake()->optional(0.4)->sentence(),
                ]);
            }
        }

        // ─── DIAGNOSTICS ─────────────────────────────────────────
        $diagnosisDescriptions = [
            'Hipertensão arterial controlada com medicação.',
            'Diabetes tipo 2 em acompanhamento regular.',
            'Paciente apresenta melhora do quadro de ansiedade.',
            'Exames laboratoriais dentro da normalidade.',
            'Sinais de melhora no quadro dermatológico.',
            'Necessário retorno em 30 dias para reavaliação.',
            'Encaminhado para exames complementares.',
            'Prescrito antibiótico por 7 dias.',
            'Quadro de enxaqueca crônica em tratamento.',
            'Acompanhamento pós-operatório satisfatório.',
        ];

        foreach ($patients as $patient) {
            $numDiags = rand(1, 3);
            for ($j = 0; $j < $numDiags; $j++) {
                Diagnostic::create([
                    'patient_id' => $patient->id,
                    'professional_id' => $professionals[array_rand($professionals)]->id,
                    'description' => $diagnosisDescriptions[array_rand($diagnosisDescriptions)],
                    'diagnosis_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
                ]);
            }
        }

        // ─── MESSAGES ────────────────────────────────────────────
        $subjects = [
            'Encaminhamento de paciente',
            'Solicitação de exames',
            'Agendamento de cirurgia',
            'Resultado de exames',
            'Alteração de prescrição',
            'Parecer médico',
            'Solicitação de prontuário',
            'Confirmação de consulta',
            'Relatório de atendimento',
            'Comunicado importante',
        ];

        $allUsers = User::all();
        for ($i = 0; $i < 15; $i++) {
            $sender = $allUsers->random();
            $recipient = $allUsers->where('id', '!=', $sender->id)->random();

            Message::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'subject' => $subjects[array_rand($subjects)],
                'body' => fake()->paragraph(3),
                'is_read' => fake()->boolean(40),
            ]);
        }

        // ─── REPORTS ─────────────────────────────────────────────
        $reportTypes = ['financial', 'clinical', 'operational', 'statistics'];
        for ($i = 0; $i < 5; $i++) {
            Report::create([
                'generated_by' => $allUsers->random()->id,
                'type' => $reportTypes[array_rand($reportTypes)],
                'title' => "Relatório " . fake()->randomElement(['Mensal', 'Trimestral', 'Anual', 'Operacional']) . " - " . fake()->monthName(),
                'data' => json_encode([
                    'total_patients' => rand(50, 200),
                    'total_appointments' => rand(100, 500),
                    'revenue' => rand(10000, 100000),
                ]),
                'period_start' => fake()->dateTimeBetween('-6 months', '-3 months')->format('Y-m-d'),
                'period_end' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            ]);
        }

        // ─── UNAVAILABILITY PERIODS ──────────────────────────────
        $reasons = ['Férias', 'Licença médica', 'Plantão externo', 'Congresso', 'Curso', 'Licença maternidade'];

        foreach ($professionals as $prof) {
            // 1-3 periods per professional, mix of past and future
            $numPeriods = rand(1, 3);
            for ($j = 0; $j < $numPeriods; $j++) {
                $isPast = fake()->boolean(30);
                $startDate = $isPast
                    ? fake()->dateTimeBetween('-6 months', '-1 month')
                    : fake()->dateTimeBetween('now', '+3 months');

                $endDate = (clone $startDate)->modify('+' . rand(3, 15) . ' days');

                UnavailabilityPeriod::create([
                    'professional_id' => $prof->id,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'reason' => $reasons[array_rand($reasons)],
                ]);
            }
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info("   Users: " . User::count());
        $this->command->info("   Patients: " . Patient::count());
        $this->command->info("   Professionals: " . Professional::count());
        $this->command->info("   Appointments: " . Appointment::count());
        $this->command->info("   Diagnostics: " . Diagnostic::count());
        $this->command->info("   Messages: " . Message::count());
        $this->command->info("   Reports: " . Report::count());
        $this->command->info("   Unavailability Periods: " . UnavailabilityPeriod::count());
    }
}
