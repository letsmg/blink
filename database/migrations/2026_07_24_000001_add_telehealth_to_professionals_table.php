<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona campos de teleatendimento e visibilidade aos profissionais.
     */
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->boolean('has_telehealth')->default(false)
                  ->after('phone2')
                  ->comment('Profissional habilitado para teleatendimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropColumn('has_telehealth');
        });
    }
};