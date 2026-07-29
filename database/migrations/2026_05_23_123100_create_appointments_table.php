<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Appointments table - appointment scheduling with payment and return flags.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('professional_id')->constrained()->onDelete('cascade');
            // location_id torna-se nullable: telehealth não exige local físico
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            // Tipo de atendimento: presencial ou teleatendimento
            $table->enum('type', ['presencial', 'telehealth'])->default('presencial');
            // Clínica onde ocorrerá o atendimento (nullable para telehealth)
            $table->foreignId('clinic_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->date('date');
            $table->time('time');
            $table->text('notes')->nullable();
            // Payment status flags
            $table->boolean('is_paid')->default(false);
            $table->string('payment_method')->nullable(); // credit_card, debit, cash, insurance, pix
            $table->dateTime('paid_at')->nullable();
            // Valor da consulta (definido pelo profissional)
            $table->decimal('amount', 10, 2)->nullable();
            // Return appointment flag
            $table->boolean('is_return')->default(false);
            $table->foreignId('original_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            // Cancelamento
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['patient', 'professional', 'clinic', 'admin'])->nullable();
            // Notas privadas
            $table->text('patient_notes')->nullable();
            $table->text('professional_notes')->nullable();
            // Consulta realizada?
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            // Vínculo com convênio e plano de saúde para cálculo financeiro
            $table->foreignId('agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('health_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Índices para performance de busca
            $table->index('type');
            $table->index('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
