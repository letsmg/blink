<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de Fornecedores — empresas/pessoas que prestam serviços ou
     * fornecem produtos para a clínica. Vinculada à tabela accounts_payable.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Razão social / nome
            $table->string('trade_name')->nullable(); // Nome fantasia
            // CNPJ com paridade PII — opcional pois pode ser pessoa física
            $table->string('cnpj_hash')->nullable()->unique();
            $table->text('cnpj_encrypted')->nullable();
            // CPF com paridade PII — opcional, para fornecedor pessoa física
            $table->string('cpf_hash')->nullable()->unique();
            $table->text('cpf_encrypted')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable(); // Observações gerais
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};