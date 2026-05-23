<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates user_geolocations table to track IP and location history of authenticated users.
     *
     * Intenção: Manter histórico separado de IPs e geolocalização de usuários logados
     * para conformidade com LGPD e auditoria de segurança. Cada login/aceite de termos
     * gera um registro independente.
     */
    public function up(): void
    {
        Schema::create('user_geolocations', function (Blueprint $table) {
            $table->id();

            // Usuário logado
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Dados da conexão
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Geolocalização
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Relação com aceite de termos (se aplicável)
            $table->string('term_type', 20)->nullable()->comment('terms_of_use, privacy_policy, both, or null if just a login');
            $table->string('terms_version', 20)->nullable();

            $table->timestamps();

            // Índices para consultas eficientes
            $table->index('user_id');
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_geolocations');
    }
};
