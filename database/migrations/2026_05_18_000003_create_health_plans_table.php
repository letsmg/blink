<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela de Planos de Saúde — planos disponíveis para os pacientes.
     * Vinculados aos convênios para cálculo dinâmico de cobrança por consulta.
     */
    public function up(): void
    {
        Schema::create('health_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do plano
            $table->string('code')->nullable(); // Código ANS ou referência
            $table->foreignId('agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', ['individual', 'family', 'corporate', 'collective'])->default('individual');
            $table->boolean('is_active')->default(true);
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_plans');
    }
};