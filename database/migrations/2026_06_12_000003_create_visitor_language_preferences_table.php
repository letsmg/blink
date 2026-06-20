<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visitor_language_preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_id')->unique();
            $table->string('preferred_locale')->nullable(false);
            $table->string('origin')->nullable(false)->default('browser');
            $table->string('country_code')->nullable();
            $table->boolean('first_visit')->default(true);
            $table->timestamp('first_visit_at')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_language_preferences');
    }
};
