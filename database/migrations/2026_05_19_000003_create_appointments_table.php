<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Appointments table - appointment scheduling with payment and return flags.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('professional_id')->constrained()->onDelete('cascade');
            $table->dateTime('appointment_date');
            $table->text('notes')->nullable();
            // Payment status flags
            $table->boolean('is_paid')->default(false);
            $table->string('payment_method')->nullable(); // credit_card, debit, cash, insurance, pix
            $table->dateTime('paid_at')->nullable();
            // Return appointment flag
            $table->boolean('is_return')->default(false);
            $table->foreignId('original_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
