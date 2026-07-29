<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Habilita Row Level Security (RLS) nas tabelas sensíveis e cria políticas
     * baseadas nas variáveis de sessão 'app.current_user_role' e 'app.current_user_id'
     * injetadas pelo middleware SetPostgresSessionVariables.
     *
     * Regras de visibilidade:
     * - 'admin' + 'adminop': acesso total (sem restrição RLS)
     * - 'prof': acesso apenas aos registros vinculados ao seu professional_id
     * - 'patient': acesso apenas aos seus próprios registros (patient_id ou user_id)
     */
    public function up(): void
    {
        // Garante que a role 'authenticated' exista antes de criar as políticas RLS.
        // Em instâncias recém-criadas do PostgreSQL essa role pode não estar presente,
        // o que causa falha nas cláusulas TO authenticated das políticas.
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'authenticated') THEN
                    CREATE ROLE authenticated NOLOGIN;
                END IF;
            END $$;
        ");

        // ================================================================
        // Tabela: appointments (Agendamentos)
        // ================================================================
        DB::statement('ALTER TABLE appointments ENABLE ROW LEVEL SECURITY');

        // Admin e AdminOperational: acesso total (USING true = sem restrição)
        DB::statement("
            CREATE POLICY appointments_admin_access ON appointments
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') IN ('admin', 'adminop')
                )
        ");

        // Professional: acesso apenas aos agendamentos vinculados a ele
        DB::statement("
            CREATE POLICY appointments_prof_access ON appointments
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') = 'prof'
                    AND professional_id = (
                        SELECT id FROM professionals WHERE user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                    )
                )
        ");

        // Patient: acesso exclusivamente aos seus próprios agendamentos
        DB::statement("
            CREATE POLICY appointments_patient_access ON appointments
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') = 'patient'
                    AND patient_id = (
                        SELECT id FROM patients WHERE user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                    )
                )
        ");

        // Força RLS para o owner da tabela também (máxima segurança)
        DB::statement('ALTER TABLE appointments FORCE ROW LEVEL SECURITY');

        // ================================================================
        // Tabela: patients (Dados de Pacientes)
        // ================================================================
        DB::statement('ALTER TABLE patients ENABLE ROW LEVEL SECURITY');

        // Admin e AdminOperational: acesso total
        DB::statement("
            CREATE POLICY patients_admin_access ON patients
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') IN ('admin', 'adminop')
                )
        ");

        // Professional: acesso apenas aos pacientes que possuem agendamentos com ele
        // (um profissional só deve ver pacientes com quem já teve/tem consulta vinculada)
        DB::statement("
            CREATE POLICY patients_prof_access ON patients
                FOR SELECT
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') = 'prof'
                    AND EXISTS (
                        SELECT 1 FROM appointments a
                        WHERE a.patient_id = patients.id
                        AND a.professional_id = (
                            SELECT id FROM professionals WHERE user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                        )
                    )
                )
        ");

        // Patient: acesso apenas ao seu próprio registro
        DB::statement("
            CREATE POLICY patients_self_access ON patients
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') = 'patient'
                    AND user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                )
        ");

        DB::statement('ALTER TABLE patients FORCE ROW LEVEL SECURITY');

        // ================================================================
        // Tabela: diagnostics (Diagnósticos/Histórico Clínico)
        // ================================================================
        DB::statement('ALTER TABLE diagnostics ENABLE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY diagnostics_admin_access ON diagnostics
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') IN ('admin', 'adminop')
                )
        ");

        // Professional: vê apenas diagnósticos que ele mesmo registrou
        DB::statement("
            CREATE POLICY diagnostics_prof_access ON diagnostics
                FOR ALL
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') = 'prof'
                    AND professional_id = (
                        SELECT id FROM professionals WHERE user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                    )
                )
        ");

        // Patient: vê apenas diagnósticos vinculados ao seu patient_id
        DB::statement("
            CREATE POLICY diagnostics_patient_access ON diagnostics
                FOR SELECT
                TO authenticated
                USING (
                    NULLIF(current_setting('app.current_user_role', true), '') = 'patient'
                    AND patient_id = (
                        SELECT id FROM patients WHERE user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                    )
                )
        ");

        DB::statement('ALTER TABLE diagnostics FORCE ROW LEVEL SECURITY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove políticas e desabilita RLS

        DB::statement('DROP POLICY IF EXISTS appointments_admin_access ON appointments');
        DB::statement('DROP POLICY IF EXISTS appointments_prof_access ON appointments');
        DB::statement('DROP POLICY IF EXISTS appointments_patient_access ON appointments');
        DB::statement('ALTER TABLE appointments NO FORCE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE appointments DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS patients_admin_access ON patients');
        DB::statement('DROP POLICY IF EXISTS patients_prof_access ON patients');
        DB::statement('DROP POLICY IF EXISTS patients_self_access ON patients');
        DB::statement('ALTER TABLE patients NO FORCE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE patients DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS diagnostics_admin_access ON diagnostics');
        DB::statement('DROP POLICY IF EXISTS diagnostics_prof_access ON diagnostics');
        DB::statement('DROP POLICY IF EXISTS diagnostics_patient_access ON diagnostics');
        DB::statement('ALTER TABLE diagnostics NO FORCE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE diagnostics DISABLE ROW LEVEL SECURITY');
    }
};