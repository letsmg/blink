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
        Schema::table('visitor_language_preferences', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('country_code');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->boolean('terms_accepted')->default(false)->after('user_agent');
            $table->boolean('privacy_accepted')->default(false)->after('terms_accepted');
            $table->timestamp('accepted_at')->nullable()->after('privacy_accepted');
            $table->string('terms_version')->nullable()->after('accepted_at');
            $table->string('privacy_version')->nullable()->after('terms_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_language_preferences', function (Blueprint $table): void {
            $table->dropColumn(['privacy_version', 'terms_version', 'accepted_at', 'privacy_accepted', 'terms_accepted', 'user_agent', 'ip_address']);
        });
    }
};
