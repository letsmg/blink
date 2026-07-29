<?php

namespace App\Repositories;

use App\Models\VisitorLanguagePreference;
use Illuminate\Support\Str;

class VisitorLanguageRepository
{
    public function findByVisitorId(string $visitorId): ?VisitorLanguagePreference
    {
        return VisitorLanguagePreference::where('visitor_id', $visitorId)->first();
    }

    public function create(array $attributes): VisitorLanguagePreference
    {
        $attributes['visitor_id'] = $attributes['visitor_id'] ?? Str::uuid()->toString();

        return VisitorLanguagePreference::create($attributes);
    }

    public function save(VisitorLanguagePreference $preference): VisitorLanguagePreference
    {
        $preference->save();

        return $preference;
    }
}
