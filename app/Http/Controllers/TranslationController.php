<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller responsável por servir as traduções do frontend.
 * 
 * As traduções são carregadas dos arquivos PHP em resources/lang/
 * e servidas como JSON para o Vue.js via endpoint /api/translations.
 */
class TranslationController extends Controller
{
    /**
     * Retorna todas as traduções disponíveis.
     */
    public function index(): JsonResponse
    {
        $locales = ['pt', 'en', 'es'];
        $translations = [];

        foreach ($locales as $locale) {
            $path = resource_path("lang/{$locale}/frontend.php");
            if (file_exists($path)) {
                $translations[$locale] = require $path;
            }
        }

        return response()->json($translations);
    }

    /**
     * Retorna as traduções para um locale específico.
     */
    public function show(string $locale): JsonResponse
    {
        $path = resource_path("lang/{$locale}/frontend.php");
        
        if (!file_exists($path)) {
            return response()->json(['error' => 'Locale not found'], 404);
        }

        return response()->json(require $path);
    }
}