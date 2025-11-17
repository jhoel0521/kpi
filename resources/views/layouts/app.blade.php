<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-s8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>


    <!-- Aquí puedes añadir tus scripts de app.js si los tienes -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Barra de navegación -->
        <x-navbar />

        <!-- Contenido de la Página -->
        <main>
            <!--
              $slot es la variable mágica que renderiza todo
              el contenido que pongas DENTRO de la etiqueta <x-app-layout>
            -->
            {{ $slot }}
        </main>
    </div>
</body>

</html>
