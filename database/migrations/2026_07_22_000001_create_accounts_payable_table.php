<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela de Contas a Pagar — despesas operacionais da clínica.
     * Atrela vencimento, valor, status de pagamento, categoria e auditoria de transações.
     */
    public function up(): void
    {
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->string('description'); // Descrição da despesa
            $table->decimal('amount', 12, 2); // Valor da despesa
            $table->date('due_date'); // Data de vencimento
            $table->date('paid_at')->nullable(); // Data em que foi pago
            $table->enum('status', ['pending', 'paid', 'overdue', 'canceled'])->default('pending');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); // Fornecedor vinculado
            $table->string('category')->nullable(); // Categoria: aluguel, salário, material, etc.
            $table->string('payment_method')->nullable(); // Método de pagamento
            $table->text('notes')->nullable(); // Observações
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para consultas comuns (por status e vencimento)
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payable');
    }
};