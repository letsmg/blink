<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Professionals table - doctors, dentists, physiotherapists, etc.
     */
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('specialty')->nullable(); // Medical specialty
            $table->string('professional_document')->nullable(); // CRM/CRP/COREN number
            $table->string('phone1')->nullable();
            $table->string('phone2')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela de períodos de indisponibilidade dos profissionais
        // Permite que profissionais informem datas em que não atenderão
        // (ex: férias, plantão em outro local, licença médica)
        Schema::create('unavailability_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable(); // Motivo opcional (ex: "Férias", "Plantão externo")
            $table->timestamps();

            // Garante que não haja períodos sobrepostos para o mesmo profissional
            $table->index(['professional_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unavailability_periods');
        Schema::dropIfExists('professionals');
    }
};
