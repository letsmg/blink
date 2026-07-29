<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Agreement;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Diagnostic;
use App\Models\HealthPlan;
use App\Models\Location;
use App\Models\Message;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Report;
use App\Models\UnavailabilityPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic test data.
     * Todos os seeders usam updateOrCreate para idempotência.
     */
    public function run(): void
    {
        DB::transaction(function () {

            // =============================================
            // Usuários (10 registros)
            // =============================================
            $admin  = $this->seedUser('admin@blink.com', 'Admin', 'Sistema', UserRole::Admin);
            $oper   = $this->seedUser('operacional@blink.com', 'Operacional', 'João', UserRole::AdminOperational);
            // Admin Operacional extra (role 2) para testes
            $this->seedUser('oper2@blink.com', 'Operacional', 'Dois', UserRole::AdminOperational);

            $patientUsers   = [];
            $patientNames   = [
                ['paciente@blink.com',     'Maria',   'Silva'],
                ['patient1@blink.com',     'João',    'Santos'],
                ['patient2@blink.com',     'Ana',     'Oliveira'],
                ['patient3@blink.com',     'Pedro',   'Costa'],
                ['patient4@blink.com',     'Carla',   'Pereira'],
                ['patient5@blink.com',     'Lucas',   'Almeida'],
                ['patient6@blink.com',     'Julia',   'Ferreira'],
                ['patient7@blink.com',     'Rafael',  'Lima'],
                ['patient8@blink.com',     'Beatriz', 'Souza'],
                ['patient9@blink.com',     'Gabriel', 'Ribeiro'],
            ];
            foreach ($patientNames as [$email, $first, $last]) {
                $patientUsers[] = $this->seedUser($email, $first, $last, UserRole::Patient);
            }

            // =============================================
            // Health Plans (5 registros)
            // =============================================
            $planUnimed = HealthPlan::updateOrCreate(
                ['code' => 'UNIMED-001'],
                [
                    'name'       => 'Unimed - Plano Básico',
                    'category'   => 'individual',
                    'is_active'  => true,
                    'monthly_fee' => 89.90,
                ]
            );
            $planBradesco = HealthPlan::updateOrCreate(
                ['code' => 'BRAD-002'],
                [
                    'name'       => 'Bradesco Saúde - Premium',
                    'category'   => 'family',
                    'is_active'  => true,
                    'monthly_fee' => 299.90,
                ]
            );
            $planAmil = HealthPlan::updateOrCreate(
                ['code' => 'AMIL-003'],
                [
                    'name'       => 'Amil - Empresarial',
                    'category'   => 'corporate',
                    'is_active'  => true,
                    'monthly_fee' => 459.90,
                ]
            );
            $planSulamerica = HealthPlan::updateOrCreate(
                ['code' => 'SUL-004'],
                [
                    'name'       => 'SulAmérica - Coletivo',
                    'category'   => 'collective',
                    'is_active'  => true,
                    'monthly_fee' => 349.90,
                ]
            );
            $planParticular = HealthPlan::updateOrCreate(
                ['code' => 'PART-000'],
                [
                    'name'       => 'Particular (Sem Convênio)',
                    'category'   => 'individual',
                    'is_active'  => true,
                    'monthly_fee' => 0,
                ]
            );

            // =============================================
            // Companies + Agreements + Health Plans (5 empresas)
            // =============================================
            $company1 = Company::updateOrCreate(
                ['cnpj_hash' => hash('sha256', '11222333000181')],
                [
                    'name'           => 'Unimed Regional',
                    'trade_name'     => 'Unimed',
                    'cnpj_encrypted' => Crypt::encryptString('11222333000181'),
                    'contact_person' => 'Carlos Mendes',
                    'is_active'      => true,
                ]
            );
            $agreement1 = Agreement::updateOrCreate(
                ['code' => 'UNI-2026'],
                [
                    'company_id'          => $company1->id,
                    'name'                => 'Convênio Unimed - Tipo A',
                    'type'                => 'private',
                    'coverage_percentage' => 80.00,
                    'consultation_fee'    => 250.00,
                    'is_active'           => true,
                    'start_date'          => '2026-01-01',
                ]
            );

            $company2 = Company::updateOrCreate(
                ['cnpj_hash' => hash('sha256', '44555666000199')],
                [
                    'name'           => 'Bradesco Saúde S.A.',
                    'trade_name'     => 'Bradesco Saúde',
                    'cnpj_encrypted' => Crypt::encryptString('44555666000199'),
                    'contact_person' => 'Ana Paula',
                    'is_active'      => true,
                ]
            );
            $agreement2 = Agreement::updateOrCreate(
                ['code' => 'BRAD-2026'],
                [
                    'company_id'          => $company2->id,
                    'name'                => 'Convênio Bradesco - Premium',
                    'type'                => 'corporate',
                    'coverage_percentage' => 90.00,
                    'consultation_fee'    => 400.00,
                    'is_active'           => true,
                ]
            );

            $company3 = Company::updateOrCreate(
                ['cnpj_hash' => hash('sha256', '77888999000155')],
                [
                    'name'           => 'Amil Assistência Médica',
                    'trade_name'     => 'Amil',
                    'cnpj_encrypted' => Crypt::encryptString('77888999000155'),
                    'is_active'      => true,
                ]
            );
            $agreement3 = Agreement::updateOrCreate(
                ['code' => 'AMIL-2026'],
                [
                    'company_id'          => $company3->id,
                    'name'                => 'Convênio Amil - Corporativo',
                    'type'                => 'corporate',
                    'coverage_percentage' => 100.00,
                    'consultation_fee'    => 500.00,
                    'is_active'           => true,
                ]
            );

            // Empresa pública
            $companySus = Company::updateOrCreate(
                ['cnpj_hash' => hash('sha256', '00000000000000')],
                [
                    'name'           => 'SUS - Sistema Único de Saúde',
                    'trade_name'     => 'SUS',
                    'cnpj_encrypted' => Crypt::encryptString('00000000000000'),
                    'contact_person' => 'Atendimento SUS',
                    'is_active'      => true,
                ]
            );
            $agreementSus = Agreement::updateOrCreate(
                ['code' => 'SUS-2026'],
                [
                    'company_id'          => $companySus->id,
                    'name'                => 'Convênio SUS - Atendimento Público',
                    'type'                => 'public',
                    'coverage_percentage' => 100.00,
                    'consultation_fee'    => 0,
                    'is_active'           => true,
                ]
            );

            // =============================================
            // Locations (3 registros)
            // =============================================
            $locCentral = Location::updateOrCreate(
                ['name' => 'Clínica Central'],
                [
                    'address'      => 'Av. Paulista, 1000',
                    'zip_code'     => '01310-100',
                    'neighborhood' => 'Bela Vista',
                    'city'         => 'São Paulo',
                ]
            );
            $locSul = Location::updateOrCreate(
                ['name' => 'Unidade Zona Sul'],
                [
                    'address'      => 'Rua Vergueiro, 2500',
                    'zip_code'     => '04102-000',
                    'neighborhood' => 'Vila Mariana',
                    'city'         => 'São Paulo',
                ]
            );
            $locNorte = Location::updateOrCreate(
                ['name' => 'Unidade Zona Norte'],
                [
                    'address'      => 'Av. Cruzeiro do Sul, 1500',
                    'zip_code'     => '02030-000',
                    'neighborhood' => 'Santana',
                    'city'         => 'São Paulo',
                ]
            );

            // =============================================
            // Professionals (5 registros)
            // =============================================
            $proUsers = [];
            $professionalsData = [
                ['dr.Cardiologia@blink.com',   'Carlos',  'Almeida',       'Cardiologia',    'CRM-SP 12345', true],
                ['dr.Dermatologia@blink.com',  'Ana',     'Barbosa',       'Dermatologia',   'CRM-SP 67890', true],
                ['dr.Ortopedia@blink.com',     'Rafael',  'Costa',         'Ortopedia',      'CRM-SP 11111', true],
                ['dr.Pediatria@blink.com',     'Juliana', 'Dias',          'Pediatria',      'CRM-SP 22222', true],
                ['dr.Neurologia@blink.com',    'Fernando','Fernandes',     'Neurologia',     'CRM-SP 33333', true],
                ['dr.Ginecologia@blink.com',   'Marina',  'Gomes',         'Ginecologia',    'CRM-SP 44444', true],
                ['dr.Oftalmologia@blink.com',  'Thiago',  'Lima',          'Oftalmologia',   'CRM-SP 55555', true],
                ['dr.Psiquiatria@blink.com',   'Camila',  'Martins',       'Psiquiatria',    'CRM-SP 66666', true],
                ['dr.Endocrinologia@blink.com','Eduardo', 'Nogueira',      'Endocrinologia', 'CRM-SP 77777', true],
                ['dr.Urologia@blink.com',      'Patrícia','Oliveira',      'Urologia',       'CRM-SP 88888', true],
            ];

            $professionals = [];
            foreach ($professionalsData as [$email, $first, $last, $specialty, $doc, $active]) {
                $u = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'display_name'        => $first,
                        'first_name_hash'      => hash('sha256', mb_strtolower($first)),
                        'first_name_encrypted' => Crypt::encryptString(mb_strtolower($first)),
                        'last_name_hash'       => hash('sha256', mb_strtolower($last)),
                        'last_name_encrypted'  => Crypt::encryptString(mb_strtolower($last)),
                        'password'            => \Illuminate\Support\Facades\Hash::make('password'),
                        'role'                => UserRole::Professional,
                    ]
                );
                $professionals[] = Professional::updateOrCreate(
                    ['professional_document' => $doc],
                    [
                        'user_id'   => $u->id,
                        'full_name' => "$first $last",
                        'specialty' => $specialty,
                        'phone1'    => '(11) 9'.fake()->numerify('####-####'),
                        'phone2'    => '(11) 9'.fake()->numerify('####-####'),
                        'is_active' => $active,
                    ]
                );
            }

            // =============================================
            // Location-Professional pivot (vínculos)
            // =============================================
            $locations = [$locCentral, $locSul, $locNorte];
            foreach ($professionals as $i => $pro) {
                DB::table('location_professional')->updateOrInsert(
                    ['professional_id' => $pro->id, 'location_id' => $locations[$i % count($locations)]->id]
                );
                // Alguns profissionais atendem em múltiplos locais
                if ($i % 2 === 0) {
                    DB::table('location_professional')->updateOrInsert(
                        ['professional_id' => $pro->id, 'location_id' => $locCentral->id]
                    );
                }
            }

            // =============================================
            // Agreement-Professional pivot
            // =============================================
            DB::table('agreement_professional')->updateOrInsert(
                ['agreement_id' => $agreement1->id, 'professional_id' => $professionals[0]->id],
                ['custom_fee' => 200.00]
            );
            DB::table('agreement_professional')->updateOrInsert(
                ['agreement_id' => $agreement2->id, 'professional_id' => $professionals[1]->id],
                ['custom_fee' => 350.00]
            );
            DB::table('agreement_professional')->updateOrInsert(
                ['agreement_id' => $agreement3->id, 'professional_id' => $professionals[2]->id],
                ['custom_fee' => 450.00]
            );

            // =============================================
            // Patients (10 registros com PII completo)
            // =============================================
            $patients = [];
            $healthPlans = [$planUnimed, $planBradesco, $planAmil, $planSulamerica, $planParticular];
            $addresses = [
                ['Rua Augusta', 'Consolação', 'São Paulo', 'SP', '01310-000'],
                ['Av. Brigadeiro Faria Lima', 'Pinheiros', 'São Paulo', 'SP', '05426-100'],
                ['Rua Oscar Freire', 'Jardins', 'São Paulo', 'SP', '01426-000'],
                ['Rua Pamplona', 'Jardim Paulista', 'São Paulo', 'SP', '01405-000'],
                ['Av. Engenheiro Luís Carlos Berrini', 'Brooklin', 'São Paulo', 'SP', '04571-000'],
                ['Rua Frei Caneca', 'Consolação', 'São Paulo', 'SP', '01307-000'],
                ['Rua dos Pinheiros', 'Pinheiros', 'São Paulo', 'SP', '05422-000'],
                ['Rua Teodoro Sampaio', 'Pinheiros', 'São Paulo', 'SP', '05405-000'],
                ['Rua Cardeal Arcoverde', 'Pinheiros', 'São Paulo', 'SP', '05407-000'],
                ['Rua da Consolação', 'Centro', 'São Paulo', 'SP', '01301-000'],
            ];

            foreach ($patientUsers as $i => $user) {
                $addr = $addresses[$i];
                $street = $addr[0];
                $neighborhood = $addr[1];
                $city = $addr[2];
                $state = $addr[3];
                $zip = $addr[4];
                $cpf = fake()->numerify('###########');
                $phone1 = preg_replace('/\D/', '', fake()->phoneNumber());
                $phone2 = preg_replace('/\D/', '', fake()->phoneNumber());

                $patient = Patient::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'date_of_birth'         => fake()->date('Y-m-d', '-18 years'),
                        'cpf_encrypted'         => Crypt::encryptString($cpf),
                        'cpf_hash'              => hash('sha256', $cpf),
                        'main_complaint'        => fake()->sentence(5),
                        // Endereço criptografado
                        'street_hash'           => hash('sha256', mb_strtolower($street)),
                        'street_encrypted'      => Crypt::encryptString(mb_strtolower($street)),
                        'neighborhood_hash'     => hash('sha256', mb_strtolower($neighborhood)),
                        'neighborhood_encrypted'=> Crypt::encryptString(mb_strtolower($neighborhood)),
                        'city_hash'             => hash('sha256', mb_strtolower($city)),
                        'city_encrypted'        => Crypt::encryptString(mb_strtolower($city)),
                        'state'                 => $state,
                        'zip_code'              => $zip,
                        'clinical_history'      => 'Histórico clínico do paciente gerado pelo sistema.',
                        // Telefones em texto puro
                        'phone1'                => $phone1,
                        'phone2'                => $phone2,
                        'health_plan_id'        => $healthPlans[$i % count($healthPlans)]->id,
                    ]
                );
                $patients[] = $patient;
            }

            // =============================================
            // Appointments (10 registros)
            // =============================================
            for ($i = 0; $i < 10; $i++) {
                $date = now()->addDays(rand(1, 30))->toDateString();
                $time = sprintf('%02d:%02d', rand(8, 17), [0, 30][rand(0, 1)]);

                Appointment::updateOrCreate(
                    [
                        'patient_id'      => $patients[$i]->id,
                        'professional_id' => $professionals[$i % count($professionals)]->id,
                        'date'            => $date,
                        'time'            => $time,
                    ],
                    [
                        'location_id' => $locCentral->id,
                        'notes'       => fake()->sentence(3),
                        'is_paid'     => false,
                        'is_return'   => $i > 5,
                        'agreement_id'=> $i % 2 === 0 ? $agreement1->id : null,
                        'health_plan_id' => $patients[$i]->health_plan_id,
                    ]
                );
            }

            // =============================================
            // Diagnostics (5 registros)
            // =============================================
            for ($i = 0; $i < 5; $i++) {
                Diagnostic::updateOrCreate(
                    [
                        'patient_id'      => $patients[$i]->id,
                        'professional_id' => $professionals[$i % count($professionals)]->id,
                        'diagnosis_date'  => now()->subDays(rand(1, 30))->toDateString(),
                    ],
                    [
                        'description'  => fake()->sentence(4),
                        'prescription' => fake()->sentence(3),
                        'notes'        => fake()->sentence(5),
                    ]
                );
            }

            // =============================================
            // Messages (5 registros)
            // =============================================
            for ($i = 0; $i < 5; $i++) {
                Message::updateOrCreate(
                    [
                        'sender_id'    => $professionals[$i % count($professionals)]->user_id,
                        'recipient_id' => $patients[$i]->user_id,
                        'subject'      => 'Mensagem de acompanhamento #'.$i,
                    ],
                    [
                        'body'   => 'Olá! Esta é uma mensagem de acompanhamento da sua consulta. Favor confirmar recebimento.',
                        'is_read' => $i % 2 === 0,
                        'read_at' => $i % 2 === 0 ? now() : null,
                    ]
                );
            }

            // =============================================
            // Unavailability Periods (5 registros)
            // =============================================
            for ($i = 0; $i < 5; $i++) {
                UnavailabilityPeriod::updateOrCreate(
                    [
                        'professional_id' => $professionals[$i]->id,
                        'start_date'      => now()->addDays(rand(5, 10))->toDateString(),
                        'end_date'        => now()->addDays(rand(11, 15))->toDateString(),
                    ],
                    [
                        'reason' => ['Férias', 'Congresso', 'Licença médica', 'Plantão externo', 'Folga'][$i],
                    ]
                );
            }

            // =============================================
            // Reports (2 registros)
            // =============================================
            Report::updateOrCreate(
                ['title' => 'Relatório de Pacientes - '.now()->format('m/Y')],
                [
                    'type'        => 'patients',
                    'data'        => json_encode(['total' => 10, 'new' => 3]),
                    'generated_by'=> $admin->id,
                ]
            );
            Report::updateOrCreate(
                ['title' => 'Relatório Financeiro - '.now()->format('m/Y')],
                [
                    'type'        => 'financial',
                    'data'        => json_encode(['revenue' => 50000, 'expenses' => 30000]),
                    'generated_by'=> $admin->id,
                ]
            );

            // =============================================
            // Accounts Payable (5 registros)
            // =============================================
            $payables = [
                ['Aluguel da clínica', 5000.00, now()->addDays(10), 'aluguel', 'pending'],
                ['Material de escritório', 350.00, now()->addDays(5), 'material', 'pending'],
                ['Energia elétrica', 1200.00, now()->addDays(3), 'utilidades', 'pending'],
                ['Salário Dr. Carlos', 15000.00, now()->addDays(15), 'salario', 'pending'],
                ['Internet e telefone', 450.00, now()->subDays(5), 'utilidades', 'paid'],
            ];
            foreach ($payables as [$desc, $amount, $due, $cat, $status]) {
                AccountPayable::updateOrCreate(
                    [
                        'description' => $desc,
                        'due_date'    => $due->toDateString(),
                    ],
                    [
                        'amount'   => $amount,
                        'status'   => $status,
                        'category' => $cat,
                        'paid_at'  => $status === 'paid' ? now()->subDays(2) : null,
                        'created_by' => $admin->id,
                    ]
                );
            }

            // =============================================
            // Accounts Receivable (5 registros)
            // =============================================
            $firstAppointment = Appointment::first();
            for ($i = 0; $i < 5; $i++) {
                AccountReceivable::updateOrCreate(
                    [
                        'appointment_id' => $firstAppointment ? $firstAppointment->id + $i : 1,
                        'patient_id'     => $patients[$i]->id,
                    ],
                    [
                        'amount'                   => 300.00 + ($i * 50),
                        'insurance_covered_amount' => $i < 3 ? 200.00 : 0,
                        'patient_portion'          => $i < 3 ? 100.00 + ($i * 50) : 300.00 + ($i * 50),
                        'due_date'                 => now()->addDays(rand(10, 30))->toDateString(),
                        'status'                   => 'pending',
                        'created_by'               => $admin->id,
                    ]
                );
            }
        });

        // Exibe resumo
        $this->command?->info("\n✅ Database seeded successfully!");
        $this->command?->info('   Users: '.User::count());
        $this->command?->info('   Patients: '.Patient::count());
        $this->command?->info('   Professionals: '.Professional::count());
        $this->command?->info('   Appointments: '.Appointment::count());
        $this->command?->info('   Diagnostics: '.Diagnostic::count());
        $this->command?->info('   Messages: '.Message::count());
        $this->command?->info('   Reports: '.Report::count());
        $this->command?->info('   Unavailability Periods: '.UnavailabilityPeriod::count());
        $this->command?->info('   Companies: '.Company::count());
        $this->command?->info('   Agreements: '.Agreement::count());
        $this->command?->info('   Health Plans: '.HealthPlan::count());
        $this->command?->info('   Accounts Payable: '.AccountPayable::count());
        $this->command?->info('   Accounts Receivable: '.AccountReceivable::count());
    }

    private function seedUser(string $email, string $firstName, string $lastName, UserRole $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'display_name'        => $firstName,
                'first_name_hash'      => hash('sha256', mb_strtolower($firstName)),
                'first_name_encrypted' => Crypt::encryptString(mb_strtolower($firstName)),
                'last_name_hash'       => hash('sha256', mb_strtolower($lastName)),
                'last_name_encrypted'  => Crypt::encryptString(mb_strtolower($lastName)),
                'password'             => \Illuminate\Support\Facades\Hash::make('password'),
                'role'                 => $role,
                'terms_accepted'       => true,
                'terms_accepted_at'    => now(),
                'terms_version'        => '1.0',
            ]
        );
    }
}