<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela de Contas a Receber — entradas financeiras integradas aos agendamentos.
     * Cada conta a receber está vinculada a um appointment e considera o plano de saúde
     * do paciente para cálculo dinâmico da cobrança.
     */
    public function up(): void
    {
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2); // Valor a receber
            $table->decimal('insurance_covered_amount', 12, 2)->default(0); // Cobertura do convênio
            $table->decimal('patient_portion', 12, 2)->default(0); // Parte do paciente
            $table->date('due_date'); // Data de vencimento
            $table->date('paid_at')->nullable(); // Data do pagamento
            $table->enum('status', ['pending', 'paid', 'overdue', 'canceled', 'invoiced'])->default('pending');
            $table->string('payment_method')->nullable(); // Método de pagamento
            $table->string('invoice_number')->nullable(); // Número da nota fiscal/fatura
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para consultas
            $table->index(['status', 'due_date']);
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};