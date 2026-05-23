<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="index, follow">

        {{-- SEO Meta Tags Base --}}
        <title>@yield('title', 'Blink - Sistema Inteligente de Gestão de Clínicas e Consultórios')</title>
        <meta name="description" content="@yield('description', 'Blink é o sistema inteligente de gestão de clínicas e consultórios. Agende consultas, gerencie pacientes e organize sua agenda com praticidade e segurança.')">
        <meta name="keywords" content="@yield('keywords', 'gestão de clínicas, agendamento médico, sistema de saúde, prontuário digital, consultório, clínica médica')">

        {{-- Open Graph --}}
        <meta property="og:title" content="@yield('og:title', 'Blink - Sistema Inteligente de Gestão de Clínicas e Consultórios')" />
        <meta property="og:description" content="@yield('og:description', 'Blink é o sistema inteligente de gestão de clínicas e consultórios. Agende consultas, gerencie pacientes e organize sua agenda com praticidade e segurança.')" />
        <meta property="og:type" content="@yield('og:type', 'website')" />
        <meta property="og:url" content="@yield('og:url', url()->current())" />
        <meta property="og:image" content="@yield('og:image', asset('storage/og-image.png'))" />

        {{-- Twitter Cards --}}
        <meta name="twitter:card" content="@yield('twitter:card', 'summary_large_image')" />
        <meta name="twitter:title" content="@yield('twitter:title', 'Blink - Sistema Inteligente de Gestão de Clínicas e Consultórios')" />
        <meta name="twitter:description" content="@yield('twitter:description', 'Blink é o sistema inteligente de gestão de clínicas e consultórios. Agende consultas, gerencie pacientes e organize sua agenda com praticidade e segurança.')" />
        <meta name="twitter:image" content="@yield('twitter:image', asset('storage/og-image.png'))" />

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
