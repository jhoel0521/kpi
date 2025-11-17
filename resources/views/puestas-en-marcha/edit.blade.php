<!-- Ruta: resources/views/puestas-en-marcha/edit.blade.php -->

<x-app-layout title="Finalizar Puesta en Marcha">

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-bold mb-4">Finalizar Puesta en Marcha</h1>
        <p class="text-gray-600 mb-4">
            Finalizando PEM #{{ $puestaEnMarcha->id }} para la jornada
            <strong>{{ $puestaEnMarcha->jornada->nombre }}</strong>
        </p>

        <div class="p-6 bg-white border rounded-lg shadow-sm">
            <!--
              Llamamos al componente.
              Como pasamos 'puestaEnMarcha', el componente sabrá que es 'edit'.
            -->
            <x-puesta-en-marcha-form :puestaEnMarcha="$puestaEnMarcha" />
        </div>
    </div>

</x-app-layout>
