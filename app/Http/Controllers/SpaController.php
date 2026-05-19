<?php

namespace App\Http\Controllers;

/**
 * Controller responsible for rendering the SPA entry point.
 * Using a controller instead of a closure allows route caching to work correctly.
 */
class SpaController extends Controller
{
    /**
     * Render the Vue.js SPA entry point.
     */
    public function index()
    {
        return view('welcome');
    }
}
