<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLanguagePreference extends Model
{
    use HasFactory;

    protected $table = 'visitor_language_preferences';

    protected $connection = 'visitor_language';

    protected $fillable = [
        'visitor_id',
        'preferred_locale',
        'origin',
        'country_code',
        'ip_address',
        'user_agent',
        'terms_accepted',
        'privacy_accepted',
        'accepted_at',
        'terms_version',
        'privacy_version',
        'first_visit',
        'first_visit_at',
        'last_visit_at',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'privacy_accepted' => 'boolean',
        'first_visit' => 'boolean',
        'accepted_at' => 'datetime',
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
    ];

    public static function supportedLocales(): array
    {
        return config('app.supported_locales', ['pt', 'en', 'es']);
    }
}
