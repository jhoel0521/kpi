<!-- Ruta: resources/views/puestas-en-marcha/create.blade.php -->

<x-app-layout title="Iniciar Puesta en Marcha">

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-bold mb-4">Iniciar Puesta en Marcha</h1>
        <p class="text-gray-600 mb-4">
            Iniciando para la jornada <strong>{{ $jornada->nombre }}</strong> (Máquina: {{ $jornada->maquina->nombre }})
        </p>

        <div class="p-6 bg-white border rounded-lg shadow-sm">
            <!--
              Llamamos al componente.
              Como solo pasamos 'jornada', el componente sabrá que es 'create'.
            -->
            <x-puesta-en-marcha-form :jornada="$jornada" />
        </div>
    </div>

</x-app-layout>
