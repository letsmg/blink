<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates term_acceptances table to persist visitor consent with IP and geolocation.
     *
     * Intenção: Registrar permanentemente no banco de dados o aceite dos termos
     * por visitantes (não logados) e usuários logados, incluindo IP e geolocalização
     * para conformidade com LGPD. O aceite NÃO fica apenas em cookies/sessão.
     */
    public function up(): void
    {
        Schema::create('term_acceptances', function (Blueprint $table) {
            $table->id();

            // Quem aceitou (nullable para visitantes não logados)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Tipo de termo aceito
            $table->enum('term_type', ['terms_of_use', 'privacy_policy', 'both'])->default('both');

            // IP e geolocalização do visitante no momento do aceite
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // User-agent do navegador/dispositivo
            $table->text('user_agent')->nullable();

            // Versão dos termos aceitos (para rastrear mudanças futuras)
            $table->string('terms_version', 20)->default('1.0');

            $table->timestamps();

            // Índices para consultas eficientes
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_acceptances');
    }
};
