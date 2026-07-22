<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela pivô: profissionais autorizados por convênio.
     * Criada separadamente para respeitar ordem de dependência com professionals.
     * Cada tipo de convênio dá acesso a diferentes grupos de profissionais.
     */
    public function up(): void
    {
        Schema::create('agreement_professional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->foreignId('professional_id')->constrained()->onDelete('cascade');
            $table->decimal('custom_fee', 12, 2)->nullable(); // Valor diferenciado
            $table->timestamps();

            $table->unique(['agreement_id', 'professional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_professional');
    }
};