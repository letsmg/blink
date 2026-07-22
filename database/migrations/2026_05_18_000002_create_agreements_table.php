<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela de Convênios — regras do convênio vinculadas a uma empresa.
     * A tabela pivô agreement_professional é criada em migration separada
     * para respeitar a ordem de dependência com professionals.
     */
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nome do convênio
            $table->string('code')->nullable(); // Código de referência do convênio
            $table->enum('type', ['private', 'public', 'corporate'])->default('private');
            $table->decimal('coverage_percentage', 5, 2)->default(100.00); // % de cobertura
            $table->decimal('consultation_fee', 12, 2)->default(0); // Valor base da consulta
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};