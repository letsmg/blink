<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Patients table - PII parity: CPF encrypted, phones encrypted, address encrypted.
     * Todos os dados sensíveis seguem o padrão _hash (SHA-256) + _encrypted (AES-256).
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->date('date_of_birth');
            // CPF com paridade PII
            $table->text('cpf_encrypted');
            $table->string('cpf_hash')->unique();
            $table->text('main_complaint')->nullable();
            // Endereço com paridade PII
            $table->string('street_hash')->nullable()->index();
            $table->text('street_encrypted')->nullable();
            $table->string('neighborhood_hash')->nullable()->index();
            $table->text('neighborhood_encrypted')->nullable();
            $table->string('city_hash')->nullable()->index();
            $table->text('city_encrypted')->nullable();
            $table->string('state', 2)->nullable(); // UF pode ficar em texto puro por não ser PII individual
            $table->string('zip_code', 10)->nullable();
            $table->text('clinical_history')->nullable();
            // Telefones em texto puro — não são considerados PII sensível
            $table->string('phone1', 20)->nullable();
            $table->string('phone2', 20)->nullable();
            // Dados do acompanhante — apenas encrypted (acesso restrito aos profissionais que atendem)
            $table->text('companion_first_name_encrypted')->nullable();
            $table->text('companion_phone_encrypted')->nullable();
            // Plano de saúde
            $table->foreignId('health_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};